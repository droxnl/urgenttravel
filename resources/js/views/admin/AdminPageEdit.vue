<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppIcon from '../../components/AppIcon.vue';
import BlockEditor from '../../components/admin/BlockEditor.vue';
import RichTextEditor from '../../components/admin/RichTextEditor.vue';
import { api, validationMessage } from '../../lib/api';

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const saving = ref(false);
const uploading = ref(false);
const error = ref('');
const notice = ref('');
const tab = ref('content');
const media = ref([]);
const showMedia = ref(false);
const aiBusy = ref('');
const aiInstruction = ref('');
const form = reactive({});
const schemaText = ref('');
const schemaTextNl = ref('');
const generateSeo = ref(false);
const savingTranslation = ref(false);

const mediaImages = computed(() => media.value.filter((item) => !item.name.includes('-32x32')));
const dutchTranslation = computed(() => form.translations?.find((translation) => translation.locale === 'nl'));

async function load() {
    try {
        const page = await api.adminPage(route.params.id);
        Object.assign(form, page);
        schemaText.value = page.schema_json ? JSON.stringify(page.schema_json, null, 2) : '';
        schemaTextNl.value = dutchTranslation.value?.schema_json
            ? JSON.stringify(dutchTranslation.value.schema_json, null, 2)
            : '';
    } catch (exception) {
        error.value = validationMessage(exception);
    } finally {
        loading.value = false;
    }
}

async function saveTranslation() {
    if (!dutchTranslation.value) return;
    savingTranslation.value = true;
    error.value = '';
    notice.value = '';
    try {
        const updated = await api.adminUpdatePageTranslation(form.id, 'nl', {
            ...dutchTranslation.value,
            schema_json: schemaTextNl.value,
        });
        const blocksResponse = await api.adminUpdatePageBlocks(form.id, 'nl', {
            blocks: dutchTranslation.value.blocks || [],
        });
        form.translations = blocksResponse.translations;
        schemaTextNl.value = dutchTranslation.value?.schema_json
            ? JSON.stringify(dutchTranslation.value.schema_json, null, 2)
            : '';
        notice.value = 'Nederlandse pagina opgeslagen.';
    } catch (exception) {
        error.value = validationMessage(exception);
    } finally {
        savingTranslation.value = false;
    }
}

onMounted(load);

async function save() {
    saving.value = true;
    error.value = '';
    notice.value = '';
    try {
        const updated = await api.adminUpdatePage(form.id, {
            ...form,
            schema_json: schemaText.value,
            generate_ai_seo: generateSeo.value,
        });
        const blocksResponse = await api.adminUpdatePageBlocks(form.id, 'en', {
            blocks: form.blocks || [],
        });
        Object.assign(form, updated);
        form.blocks = blocksResponse.blocks || [];
        generateSeo.value = false;
        notice.value = 'Page saved. The live website is up to date.';
    } catch (exception) {
        error.value = validationMessage(exception);
    } finally {
        saving.value = false;
    }
}

async function openMedia() {
    showMedia.value = true;
    if (!media.value.length) media.value = await api.adminMedia();
}

function chooseImage(url) {
    form.hero_image = url;
    showMedia.value = false;
}

async function uploadMedia(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    uploading.value = true;
    try {
        const data = new FormData();
        data.append('image', file);
        const uploaded = await api.adminUploadMedia(data);
        form.hero_image = uploaded.url;
        media.value.unshift({ name: uploaded.name, url: uploaded.url });
        showMedia.value = false;
    } catch (exception) {
        error.value = validationMessage(exception);
    } finally {
        uploading.value = false;
    }
}

async function generate(action) {
    aiBusy.value = action;
    error.value = '';
    notice.value = '';
    try {
        const data = await api.adminGenerate(form.id, { action, instruction: aiInstruction.value });
        if (action === 'seo') {
            form.seo_title = data.seo_title || form.seo_title;
            form.seo_description = data.seo_description || form.seo_description;
            form.focus_keyword = data.focus_keyword || form.focus_keyword;
            form.og_title = form.seo_title;
            form.og_description = form.seo_description;
            tab.value = 'seo';
        } else if (action === 'hero') {
            form.hero_title = data.hero_title || form.hero_title;
            form.hero_subtitle = data.hero_subtitle || form.hero_subtitle;
            form.hero_eyebrow = data.hero_eyebrow || form.hero_eyebrow;
        } else if (action === 'body') {
            form.content_html = data.content_html || form.content_html;
            tab.value = 'content';
        }
        notice.value = 'AI draft generated. Review it, then save the page.';
    } catch (exception) {
        error.value = validationMessage(exception);
    } finally {
        aiBusy.value = '';
    }
}
</script>

<template>
    <div v-if="loading" class="admin-empty">Loading page editor…</div>
    <template v-else-if="form.id">
        <div class="admin-page-head admin-page-head--row">
            <div>
                <RouterLink to="/admin/pages" class="back-link">← All pages</RouterLink>
                <span class="eyebrow">Page editor</span>
                <h1>{{ form.title }}</h1>
                <p>{{ form.path }}</p>
            </div>
            <div class="admin-page-actions">
                <a :href="form.path" target="_blank" rel="noopener" class="button button--ghost">Preview <AppIcon name="arrow" :size="16" /></a>
                <button class="button button--gold" :disabled="saving" @click="save">{{ saving ? 'Saving…' : 'Save changes' }}</button>
            </div>
        </div>

        <p v-if="error" class="form-alert form-alert--error" role="alert">{{ error }}</p>
        <p v-if="notice" class="form-alert form-alert--success" role="status">{{ notice }}</p>

        <div class="editor-tabs" role="tablist">
            <button :class="{ active: tab === 'content' }" @click="tab = 'content'">Content</button>
            <button :class="{ active: tab === 'hero' }" @click="tab = 'hero'">Hero & media</button>
            <button :class="{ active: tab === 'blocks' }" @click="tab = 'blocks'">Structured blocks</button>
            <button :class="{ active: tab === 'seo' }" @click="tab = 'seo'">SEO</button>
            <button :class="{ active: tab === 'dutch' }" @click="tab = 'dutch'">Nederlands</button>
            <button :class="{ active: tab === 'settings' }" @click="tab = 'settings'">Page settings</button>
            <button class="editor-tabs__ai" :class="{ active: tab === 'ai' }" @click="tab = 'ai'"><AppIcon name="sparkles" :size="16" /> AI assistant</button>
        </div>

        <section v-if="tab === 'content'" class="admin-panel editor-panel">
            <div class="editor-panel__head">
                <div><h2>Main page content</h2><p>Edit the full article shown below the designed hero and service sections.</p></div>
                <span>{{ (form.content_html || '').replace(/<[^>]*>/g, '').length }} characters</span>
            </div>
            <label class="field field--editor">
                <span>Page body</span>
                <RichTextEditor v-model="form.content_html" />
            </label>
        </section>

        <section v-if="tab === 'blocks'" class="admin-panel editor-panel">
            <div class="editor-panel__head">
                <div><h2>Structured content blocks</h2><p>Edit service cards, fleet cards, coverage groups and related links as individual items.</p></div>
            </div>
            <BlockEditor v-model="form.blocks" locale="en" />
            <button class="button button--gold editor-block-save" :disabled="saving" @click="save">{{ saving ? 'Saving…' : 'Save structured blocks' }}</button>
        </section>

        <section v-if="tab === 'dutch'" class="admin-panel editor-panel">
            <div class="editor-panel__head">
                <div><h2>Nederlandse vertaling</h2><p>De volledige Nederlandse pagina, inclusief hero, inhoud en SEO.</p></div>
                <span class="template-pill">Nederlands</span>
            </div>
            <div v-if="dutchTranslation" class="form-grid form-grid--admin">
                <label class="field field--wide"><span>Pad</span><input v-model="dutchTranslation.path"></label>
                <label class="field"><span>Paginatitel</span><input v-model="dutchTranslation.title"></label>
                <label class="field"><span>Hero-label</span><input v-model="dutchTranslation.hero_eyebrow"></label>
                <label class="field field--wide"><span>Hero-titel</span><input v-model="dutchTranslation.hero_title"></label>
                <label class="field field--wide"><span>Hero-subtitel</span><textarea v-model="dutchTranslation.hero_subtitle" rows="3"></textarea></label>
                <label class="field field--wide"><span>Samenvatting</span><textarea v-model="dutchTranslation.excerpt" rows="3"></textarea></label>
                <label class="field field--wide"><span>SEO-titel</span><input v-model="dutchTranslation.seo_title"></label>
                <label class="field field--wide"><span>Meta-omschrijving</span><textarea v-model="dutchTranslation.seo_description" rows="3"></textarea></label>
                <label class="field field--wide"><span>Canonical URL</span><input v-model="dutchTranslation.canonical_url"></label>
                <label class="field"><span>Open Graph-titel</span><input v-model="dutchTranslation.og_title"></label>
                <label class="field"><span>Open Graph-omschrijving</span><textarea v-model="dutchTranslation.og_description" rows="3"></textarea></label>
                <label class="field field--wide"><span>Gestructureerde data (JSON-LD)</span><textarea v-model="schemaTextNl" rows="8" class="code-field"></textarea></label>
                <label class="field field--wide"><span>Nederlandse paginatekst</span><RichTextEditor v-model="dutchTranslation.content_html" /></label>
                <div class="field--wide">
                    <span class="eyebrow">Nederlandse structuurblokken</span>
                    <BlockEditor v-model="dutchTranslation.blocks" locale="nl" />
                </div>
            </div>
            <div v-else class="admin-empty">Geen Nederlandse vertaling gevonden voor deze pagina.</div>
            <button class="button button--gold" :disabled="savingTranslation || !dutchTranslation" @click="saveTranslation">{{ savingTranslation ? 'Opslaan…' : 'Nederlandse pagina opslaan' }}</button>
        </section>

        <section v-if="tab === 'hero'" class="admin-panel editor-panel">
            <div class="editor-panel__head"><div><h2>Hero section</h2><p>The first impression, image and supporting copy.</p></div></div>
            <div class="form-grid form-grid--admin">
                <label class="field field--wide"><span>Eyebrow</span><input v-model="form.hero_eyebrow"></label>
                <label class="field field--wide"><span>Hero heading</span><input v-model="form.hero_title"></label>
                <label class="field field--wide"><span>Hero subtitle</span><textarea v-model="form.hero_subtitle" rows="3"></textarea></label>
                <label class="field field--wide">
                    <span>Hero image</span>
                    <div class="media-field">
                        <input v-model="form.hero_image" placeholder="/images/uploads/image.jpg">
                        <button type="button" class="button button--ghost button--small" @click="openMedia">Browse media</button>
                    </div>
                </label>
            </div>
            <div v-if="form.hero_image" class="hero-image-preview">
                <img :src="form.hero_image" alt="Selected hero preview">
            </div>
        </section>

        <section v-if="tab === 'seo'" class="admin-panel editor-panel">
            <div class="editor-panel__head">
                <div><h2>Search appearance</h2><p>Preserve or refine the title, description and technical metadata.</p></div>
                <div class="seo-score" :class="{ complete: form.seo_title && form.seo_description }">
                    {{ form.seo_title && form.seo_description ? 'Complete' : 'Needs attention' }}
                </div>
            </div>
            <div class="form-grid form-grid--admin">
                <label class="field field--wide"><span>SEO title <small>{{ (form.seo_title || '').length }}/60 suggested</small></span><input v-model="form.seo_title" maxlength="255"></label>
                <label class="field field--wide"><span>Meta description <small>{{ (form.seo_description || '').length }}/160 suggested</small></span><textarea v-model="form.seo_description" rows="3" maxlength="500"></textarea></label>
                <label class="field"><span>Focus keyword</span><input v-model="form.focus_keyword"></label>
                <label class="field"><span>Robots</span><input v-model="form.robots"></label>
                <label class="field field--wide"><span>Canonical URL</span><input v-model="form.canonical_url"></label>
                <label class="field"><span>Open Graph title</span><input v-model="form.og_title"></label>
                <label class="field"><span>Open Graph image</span><input v-model="form.og_image"></label>
                <label class="field field--wide"><span>Open Graph description</span><textarea v-model="form.og_description" rows="3"></textarea></label>
                <label class="field field--wide"><span>Structured data (JSON-LD)</span><textarea v-model="schemaText" rows="10" class="code-field"></textarea></label>
            </div>
            <div class="search-preview">
                <span>Search preview</span>
                <em>{{ form.seo_title || form.title }}</em>
                <small>urgent-travelservices.nl{{ form.path }}</small>
                <p>{{ form.seo_description || form.excerpt }}</p>
            </div>
        </section>

        <section v-if="tab === 'settings'" class="admin-panel editor-panel">
            <div class="editor-panel__head"><div><h2>Page settings</h2><p>Control the URL, template, navigation order and publication state.</p></div></div>
            <div class="form-grid form-grid--admin">
                <label class="field"><span>Page title</span><input v-model="form.title" required></label>
                <label class="field"><span>Slug</span><input v-model="form.slug"></label>
                <label class="field field--wide"><span>Path</span><input v-model="form.path"></label>
                <label class="field"><span>Template</span>
                    <select v-model="form.template">
                        <option value="general">General</option>
                        <option value="home">Home</option>
                        <option value="services">Services index</option>
                        <option value="service">Service detail</option>
                        <option value="landing">Schiphol landing</option>
                        <option value="booking">Booking</option>
                        <option value="contact">Contact</option>
                        <option value="rates">Rates</option>
                        <option value="fleet">Fleet</option>
                    </select>
                </label>
                <label class="field"><span>Sort order</span><input v-model.number="form.sort_order" type="number" min="0"></label>
                <label class="checkbox-field field--wide"><input v-model="form.is_published" type="checkbox"><span>Published on the live website</span></label>
                <label class="checkbox-field field--wide"><input v-model="generateSeo" type="checkbox"><span>Generate SEO with AI when I save this page</span></label>
            </div>
        </section>

        <section v-if="tab === 'ai'" class="admin-panel editor-panel ai-panel">
            <div class="editor-panel__head">
                <div><span class="eyebrow">OpenAI writing assistant</span><h2>Turn a draft into considered copy.</h2><p>AI output is always a draft. Review facts, confirm prices and save when it feels right.</p></div>
                <AppIcon name="sparkles" :size="42" />
            </div>
            <label class="field field--wide"><span>Optional direction for the assistant</span><textarea v-model="aiInstruction" rows="3" placeholder="For example: Focus this page on business travellers arriving at Schiphol and keep the tone understated."></textarea></label>
            <div class="ai-actions">
                <button class="ai-action" :disabled="aiBusy" @click="generate('seo')"><AppIcon name="sparkles" :size="20" /><strong>{{ aiBusy === 'seo' ? 'Writing…' : 'Generate SEO' }}</strong><span>Title, description and focus keyword</span></button>
                <button class="ai-action" :disabled="aiBusy" @click="generate('hero')"><AppIcon name="sparkles" :size="20" /><strong>{{ aiBusy === 'hero' ? 'Writing…' : 'Refresh hero' }}</strong><span>Heading, eyebrow and supporting sentence</span></button>
                <button class="ai-action" :disabled="aiBusy" @click="generate('body')"><AppIcon name="sparkles" :size="20" /><strong>{{ aiBusy === 'body' ? 'Writing…' : 'Rewrite body' }}</strong><span>A refined version of the page introduction</span></button>
            </div>
            <div class="ai-assurance"><AppIcon name="shield" :size="18" /> API credentials are encrypted and never sent to the browser after saving.</div>
        </section>

        <div class="sticky-save">
            <span>{{ form.is_published ? 'Live page' : 'Draft page' }} · Last saved {{ form.modified_at ? new Date(form.modified_at).toLocaleString('en-GB') : 'not yet' }}</span>
            <button class="button button--gold" :disabled="saving" @click="save">{{ saving ? 'Saving…' : 'Save changes' }}</button>
        </div>

        <Teleport to="body">
            <div v-if="showMedia" class="media-modal" @click.self="showMedia = false">
                <div class="media-modal__panel">
                    <div class="media-modal__head">
                        <div><span class="eyebrow">Media library</span><h2>Choose an image</h2><p>{{ mediaImages.length }} local images available.</p></div>
                        <button class="icon-button" @click="showMedia = false"><AppIcon name="x" :size="22" /></button>
                    </div>
                    <label class="upload-button">
                        <AppIcon name="upload" :size="18" />
                        {{ uploading ? 'Uploading…' : 'Upload a new image' }}
                        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml" :disabled="uploading" @change="uploadMedia">
                    </label>
                    <div class="media-grid">
                        <button v-for="image in mediaImages" :key="image.name" type="button" @click="chooseImage(image.url)">
                            <img :src="image.url" :alt="image.name" loading="lazy">
                            <span>{{ image.name }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </template>
</template>
