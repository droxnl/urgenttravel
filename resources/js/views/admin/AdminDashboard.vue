<script setup>
import { onMounted, ref } from 'vue';
import AppIcon from '../../components/AppIcon.vue';
import { api } from '../../lib/api';

const stats = ref({ pages: 0, published: 0, new_inquiries: 0, total_inquiries: 0 });
const recent = ref([]);
const loading = ref(true);
const aiConfigured = ref(false);
const mailConfigured = ref(false);

onMounted(async () => {
    try {
        const [statData, inquiries, settings] = await Promise.all([
            api.adminStats(),
            api.adminInquiries(),
            api.adminSettings(),
        ]);
        stats.value = statData;
        recent.value = inquiries.data.slice(0, 5);
        aiConfigured.value = settings.openai_configured;
        mailConfigured.value = settings.mail_configured;
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="admin-page-head">
        <div><span class="eyebrow">Website overview</span><h1>Good to see you.</h1><p>Keep the story current and every journey easy to book.</p></div>
    </div>
    <div class="stat-grid">
        <article><span>Published pages</span><strong>{{ stats.published }}</strong><small>of {{ stats.pages }} total pages</small></article>
        <article><span>New inquiries</span><strong>{{ stats.new_inquiries }}</strong><small>waiting for attention</small></article>
        <article><span>All inquiries</span><strong>{{ stats.total_inquiries }}</strong><small>requests in your inbox</small></article>
        <article><span>Email notifications</span><strong class="stat-grid__status">{{ mailConfigured ? 'Active' : 'Off' }}</strong><small>{{ mailConfigured ? 'Hostinger Mail API connected' : 'configure in Email settings' }}</small><AppIcon name="mail" :size="30" /></article>
        <article class="stat-grid__accent"><span>OpenAI</span><strong class="stat-grid__status">{{ aiConfigured ? 'Configured' : 'Not configured' }}</strong><small>{{ aiConfigured ? 'write with AI from any page' : 'add a key in Settings & AI' }}</small><AppIcon name="sparkles" :size="34" /></article>
    </div>
    <section class="admin-panel">
        <div class="admin-panel__head">
            <div><h2>Latest inquiries</h2><p>The newest requests from the website.</p></div>
            <RouterLink class="text-link" to="/admin/inquiries">View all <AppIcon name="arrow" :size="16" /></RouterLink>
        </div>
        <div v-if="loading" class="admin-empty">Loading…</div>
        <div v-else-if="!recent.length" class="admin-empty">No inquiries yet. Requests from the website will appear here.</div>
        <div v-else class="admin-list">
            <RouterLink v-for="inquiry in recent" :key="inquiry.id" to="/admin/inquiries" class="admin-list__row">
                <span class="status-dot" :class="`status-dot--${inquiry.status}`"></span>
                <div><strong>{{ inquiry.name }}</strong><span>{{ inquiry.pickup || inquiry.message || 'Website inquiry' }}</span></div>
                <span>{{ inquiry.reference }}</span>
                <span>{{ new Date(inquiry.created_at).toLocaleDateString('en-GB') }}</span>
                <AppIcon name="arrow" :size="16" />
            </RouterLink>
        </div>
    </section>
</template>
