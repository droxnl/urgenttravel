<script setup>
import { watch } from 'vue';
import { useRoute } from 'vue-router';
import { usePage } from '../composables/usePage';
import BookingPage from './BookingPage.vue';
import ContactPage from './ContactPage.vue';
import ContentPage from './ContentPage.vue';
import FleetPage from './FleetPage.vue';
import HomePage from './HomePage.vue';
import RatesPage from './RatesPage.vue';
import ServicesPage from './ServicesPage.vue';
import { useI18n } from '../lib/i18n';

const route = useRoute();
const { locale, t } = useI18n();
const resolvePath = () => '/' + (route.params.pathMatch || []).join('/');
const { page, loading, error, load } = usePage(resolvePath);

watch(
    () => route.fullPath.split('?')[0],
    () => {
        if (page.value?.path !== resolvePath()) load().catch(() => {});
    },
);
</script>

<template>
    <div v-if="loading" class="page-loading container">
        <span class="loading-line"></span>
        <p>{{ t('home.loading') }}</p>
    </div>
    <div v-else-if="error" class="error-page container">
        <span class="eyebrow">404</span>
        <h1>{{ t('error.notFound') }}</h1>
        <p>{{ error }}</p>
        <RouterLink class="button button--gold" :to="locale === 'nl' ? '/nl/' : '/'">{{ t('error.back') }}</RouterLink>
    </div>
    <template v-else-if="page">
        <HomePage v-if="page.template === 'home'" />
        <BookingPage v-else-if="page.template === 'booking'" :page="page" />
        <ContactPage v-else-if="page.template === 'contact'" :page="page" />
        <ServicesPage v-else-if="page.template === 'services'" :page="page" />
        <FleetPage v-else-if="page.template === 'fleet'" :page="page" />
        <RatesPage v-else-if="page.template === 'rates'" :page="page" />
        <ContentPage v-else :page="page" />
    </template>
</template>
