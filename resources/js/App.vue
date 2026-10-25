<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import SiteHeader from './components/SiteHeader.vue';
import SiteFooter from './components/SiteFooter.vue';
import FloatingActions from './components/FloatingActions.vue';

const route = useRoute();
const isAdmin = computed(() => Boolean(route.meta.admin));
</script>

<template>
    <div v-if="isAdmin" class="admin-app">
        <RouterView />
    </div>
    <div v-else class="site-app">
        <SiteHeader />
        <main id="main-content">
            <RouterView />
        </main>
        <SiteFooter />
        <FloatingActions />
    </div>
</template>
