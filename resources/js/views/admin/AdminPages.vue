<script setup>
import { computed, onMounted, ref } from 'vue';
import AppIcon from '../../components/AppIcon.vue';
import { api, validationMessage } from '../../lib/api';

const pages = ref([]);
const search = ref('');
const loading = ref(true);
const error = ref('');

const filtered = computed(() => {
    const query = search.value.toLowerCase();
    return pages.value.filter((page) => `${page.title} ${page.path} ${page.template}`.toLowerCase().includes(query));
});

onMounted(async () => {
    try {
        pages.value = await api.adminPages();
    } catch (exception) {
        error.value = validationMessage(exception);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="admin-page-head admin-page-head--row">
        <div><span class="eyebrow">Content</span><h1>All pages</h1><p>Every page, its SEO metadata and live status in one place.</p></div>
        <div class="admin-search"><AppIcon name="pages" :size="17" /><input v-model="search" placeholder="Search pages…"></div>
    </div>
    <p v-if="error" class="form-alert form-alert--error">{{ error }}</p>
    <div class="admin-panel admin-panel--flush">
        <div class="page-table">
            <div class="page-table__head"><span>Page</span><span>Template</span><span>SEO</span><span>Status</span><span></span></div>
            <div v-if="loading" class="admin-empty">Loading pages…</div>
            <RouterLink v-for="page in filtered" :key="page.id" :to="`/admin/pages/${page.id}`" class="page-table__row">
                <div>
                    <strong>{{ page.title }}</strong>
                    <span>{{ page.path }}</span>
                </div>
                <span class="template-pill">{{ page.template }}</span>
                <span class="seo-status" :class="{ complete: page.seo_title && page.seo_description }">
                    {{ page.seo_title && page.seo_description ? 'Complete' : 'Needs attention' }}
                </span>
                <span class="publish-status" :class="{ published: page.is_published }">
                    {{ page.is_published ? 'Published' : 'Draft' }}
                </span>
                <AppIcon name="arrow" :size="16" />
            </RouterLink>
        </div>
    </div>
</template>
