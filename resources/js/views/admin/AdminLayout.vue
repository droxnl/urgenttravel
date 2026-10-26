<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppIcon from '../../components/AppIcon.vue';
import SiteLogo from '../../components/SiteLogo.vue';
import { api, initialBootstrap, validationMessage } from '../../lib/api';

const router = useRouter();
const route = useRoute();
const user = ref(initialBootstrap.user || null);
const checking = ref(true);
const error = ref('');

onMounted(async () => {
    try {
        const response = await api.adminMe();
        user.value = response.user;
    } catch (exception) {
        error.value = validationMessage(exception);
        router.replace('/admin/login');
    } finally {
        checking.value = false;
    }
});

async function logout() {
    await api.adminLogout().catch(() => {});
    router.push('/admin/login');
}
</script>

<template>
    <div v-if="checking" class="admin-checking">Checking your session…</div>
    <div v-else class="admin-shell">
        <aside class="admin-sidebar">
            <SiteLogo light />
            <span class="admin-sidebar__label">Content studio</span>
            <nav class="admin-nav">
                <RouterLink to="/admin" exact-active-class="active" aria-label="Overview">
                    <AppIcon name="dashboard" :size="19" /><span>Overview</span>
                </RouterLink>
                <RouterLink to="/admin/pages" active-class="active" aria-label="Pages">
                    <AppIcon name="pages" :size="19" /><span>Pages</span>
                </RouterLink>
                <RouterLink to="/admin/inquiries" active-class="active" aria-label="Inquiries">
                    <AppIcon name="inbox" :size="19" /><span>Inquiries</span>
                </RouterLink>
                <RouterLink to="/admin/booking-settings" active-class="active" aria-label="Booking and tariffs">
                    <AppIcon name="car" :size="19" /><span>Booking & tariffs</span>
                </RouterLink>
                <RouterLink to="/admin/settings" active-class="active" aria-label="Settings and AI">
                    <AppIcon name="settings" :size="19" /><span>Settings & AI</span>
                </RouterLink>
            </nav>
            <div class="admin-sidebar__bottom">
                <a href="/" target="_blank" rel="noopener">
                    <AppIcon name="arrow" :size="17" /> View website
                </a>
                <button type="button" @click="logout"><AppIcon name="logout" :size="17" /> Sign out</button>
            </div>
        </aside>
        <div class="admin-main">
            <header class="admin-topbar">
                <div>
                    <span>{{ route.meta.title || 'Content manager' }}</span>
                    <strong>{{ user?.name || 'Administrator' }}</strong>
                </div>
                <a href="/" target="_blank" rel="noopener" class="button button--ghost button--small">
                    Open website <AppIcon name="arrow" :size="15" />
                </a>
            </header>
            <main class="admin-content">
                <RouterView :key="route.fullPath" />
            </main>
        </div>
    </div>
</template>
