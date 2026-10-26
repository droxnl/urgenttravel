<script setup>
import { ref } from 'vue';
import AppIcon from '../AppIcon.vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    locale: { type: String, default: 'en' },
});
const emit = defineEmits(['update:modelValue']);
const newBlockType = ref('service_grid');

const blockLabels = {
    trust_strip: 'Trust strip',
    service_grid: 'Service cards',
    fleet_grid: 'Fleet cards',
    coverage_grid: 'Coverage grid',
    related_links: 'Related links',
};

function update(blocks) {
    emit('update:modelValue', blocks);
}

function blankContent(type) {
    if (type === 'service_grid') {
        return { eyebrow: '', heading_line_1: '', heading_line_2: '', intro: '', link_label: 'Explore service', items: [] };
    }
    if (type === 'fleet_grid') {
        return { eyebrow: '', heading_line_1: '', heading_line_2: '', intro: '', link_label: 'View the fleet', link: '/fleet/', items: [] };
    }
    if (type === 'coverage_grid') {
        return { eyebrow: '', heading_line_1: '', heading_line_2: '', intro: '', footer_note: '', cta_label: 'Plan a journey', cta_link: '/booking/', items: [] };
    }
    if (type === 'related_links') {
        return { eyebrow: 'Continue exploring', heading: 'Other ways to travel.', items: [] };
    }
    return { items: [] };
}

function addBlock() {
    update([
        ...props.modelValue,
        {
            id: null,
            locale: props.locale,
            type: newBlockType.value,
            content: blankContent(newBlockType.value),
            sort_order: (props.modelValue.length + 1) * 10,
            is_active: true,
        },
    ]);
}

function removeBlock(index) {
    if (!window.confirm('Remove this block from the page?')) return;
    update(props.modelValue.filter((_, itemIndex) => itemIndex !== index));
}

function moveBlock(index, direction) {
    const target = index + direction;
    if (target < 0 || target >= props.modelValue.length) return;
    const blocks = [...props.modelValue];
    [blocks[index], blocks[target]] = [blocks[target], blocks[index]];
    blocks.forEach((block, blockIndex) => { block.sort_order = (blockIndex + 1) * 10; });
    update(blocks);
}

function blankItem(type) {
    if (type === 'service_grid') return { title: 'New service', kicker: '', description: '', link: '/services/', icon: 'car', image: '' };
    if (type === 'fleet_grid') return { title: 'New vehicle class', label: '', description: '', image: '' };
    if (type === 'trust_strip') return { title: 'New benefit', text: '', icon: 'check' };
    if (type === 'coverage_grid') return { title: 'New province', links: [{ label: 'New city', url: '/' }] };
    return { title: 'New link', link: '/' };
}

function addItem(block) {
    block.content.items ||= [];
    block.content.items.push(blankItem(block.type));
}

function removeItem(block, index) {
    block.content.items.splice(index, 1);
}

function addCoverageLink(item) {
    item.links ||= [];
    item.links.push({ label: 'New city', url: '/' });
}
</script>

<template>
    <div class="block-editor">
        <div class="block-editor__toolbar">
            <div>
                <strong>Structured page blocks</strong>
                <span>These sections render as designed components, separate from the article editor.</span>
            </div>
            <div class="block-editor__add">
                <select v-model="newBlockType">
                    <option v-for="(label, type) in blockLabels" :key="type" :value="type">{{ label }}</option>
                </select>
                <button class="button button--ghost button--small" type="button" @click="addBlock">Add block</button>
            </div>
        </div>

        <div v-if="!modelValue.length" class="admin-empty">No structured blocks on this page.</div>

        <article v-for="(block, blockIndex) in modelValue" :key="block.id || `${block.type}-${blockIndex}`" class="block-editor__card">
            <header class="block-editor__card-head">
                <div>
                    <span class="template-pill">{{ blockLabels[block.type] || block.type }}</span>
                    <label class="block-active"><input v-model="block.is_active" type="checkbox"> Active</label>
                </div>
                <div class="block-editor__controls">
                    <label>Order <input v-model.number="block.sort_order" type="number" min="0" step="10"></label>
                    <button type="button" title="Move up" :disabled="blockIndex === 0" @click="moveBlock(blockIndex, -1)">↑</button>
                    <button type="button" title="Move down" :disabled="blockIndex === modelValue.length - 1" @click="moveBlock(blockIndex, 1)">↓</button>
                    <button type="button" class="danger" title="Remove block" @click="removeBlock(blockIndex)"><AppIcon name="trash" :size="16" /></button>
                </div>
            </header>

            <div v-if="block.type !== 'trust_strip'" class="form-grid form-grid--admin block-editor__headings">
                <label class="field"><span>Eyebrow</span><input v-model="block.content.eyebrow"></label>
                <label v-if="block.type === 'related_links'" class="field"><span>Heading</span><input v-model="block.content.heading"></label>
                <template v-else>
                    <label class="field"><span>Heading line 1</span><input v-model="block.content.heading_line_1"></label>
                    <label class="field"><span>Heading line 2</span><input v-model="block.content.heading_line_2"></label>
                </template>
                <label v-if="block.type !== 'related_links'" class="field field--wide"><span>Intro text</span><textarea v-model="block.content.intro" rows="2"></textarea></label>
                <label v-if="['service_grid', 'fleet_grid'].includes(block.type)" class="field"><span>Action label</span><input v-model="block.content.link_label"></label>
                <label v-if="block.type === 'fleet_grid'" class="field"><span>Action link</span><input v-model="block.content.link"></label>
                <label v-if="block.type === 'coverage_grid'" class="field field--wide"><span>Footer note</span><input v-model="block.content.footer_note"></label>
                <template v-if="block.type === 'coverage_grid'">
                    <label class="field"><span>Footer button label</span><input v-model="block.content.cta_label"></label>
                    <label class="field"><span>Footer button link</span><input v-model="block.content.cta_link"></label>
                </template>
            </div>

            <div class="block-editor__items">
                <div class="block-editor__items-head">
                    <span>Items ({{ block.content.items?.length || 0 }})</span>
                    <button class="button button--ghost button--small" type="button" @click="addItem(block)">Add item</button>
                </div>

                <div v-for="(item, itemIndex) in block.content.items" :key="itemIndex" class="block-item">
                    <div class="block-item__number">{{ itemIndex + 1 }}</div>
                    <div class="block-item__fields">
                        <template v-if="block.type === 'service_grid'">
                            <label class="field"><span>Title</span><input v-model="item.title"></label>
                            <label class="field"><span>Kicker</span><input v-model="item.kicker"></label>
                            <label class="field field--wide"><span>Description</span><textarea v-model="item.description" rows="2"></textarea></label>
                            <label class="field"><span>Link</span><input v-model="item.link"></label>
                            <label class="field"><span>Icon</span><input v-model="item.icon" placeholder="plane, crown, car..."></label>
                        </template>
                        <template v-else-if="block.type === 'fleet_grid'">
                            <label class="field"><span>Title</span><input v-model="item.title"></label>
                            <label class="field"><span>Label</span><input v-model="item.label"></label>
                            <label class="field field--wide"><span>Description</span><textarea v-model="item.description" rows="2"></textarea></label>
                            <label class="field field--wide"><span>Image URL</span><input v-model="item.image"></label>
                        </template>
                        <template v-else-if="block.type === 'trust_strip'">
                            <label class="field"><span>Title</span><input v-model="item.title"></label>
                            <label class="field"><span>Icon</span><input v-model="item.icon"></label>
                            <label class="field field--wide"><span>Text</span><textarea v-model="item.text" rows="2"></textarea></label>
                        </template>
                        <template v-else-if="block.type === 'coverage_grid'">
                            <label class="field field--wide"><span>Province / group title</span><input v-model="item.title"></label>
                            <div class="coverage-links-editor">
                                <div v-for="(link, linkIndex) in item.links" :key="linkIndex">
                                    <input v-model="link.label" placeholder="City">
                                    <input v-model="link.url" placeholder="/taxi-city-naar-schiphol/">
                                    <button type="button" @click="item.links.splice(linkIndex, 1)">×</button>
                                </div>
                                <button class="button button--ghost button--small" type="button" @click="addCoverageLink(item)">Add city</button>
                            </div>
                        </template>
                        <template v-else>
                            <label class="field"><span>Title</span><input v-model="item.title"></label>
                            <label class="field"><span>Link</span><input v-model="item.link"></label>
                        </template>
                    </div>
                    <button class="icon-button icon-button--danger" type="button" title="Remove item" @click="removeItem(block, itemIndex)"><AppIcon name="trash" :size="15" /></button>
                </div>
            </div>
        </article>
    </div>
</template>
