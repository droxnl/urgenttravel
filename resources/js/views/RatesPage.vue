<script setup>
import AppIcon from '../components/AppIcon.vue';
import PageBody from '../components/PageBody.vue';
import PageHero from '../components/PageHero.vue';
import { useI18n } from '../lib/i18n';

defineProps({ page: { type: Object, required: true } });
const { t } = useI18n();
</script>

<template>
    <PageHero
        :eyebrow="t('rates.eyebrow')"
        :title="t('rates.title')"
        :subtitle="t('rates.subtitle')"
        image="/images/uploads/WhatsApp-Image-2026-01-14-at-21.05.37-1.jpeg"
        compact
        :breadcrumbs="[{ label: t('rates.crumb') }]"
    />
    <section class="rate-promises section">
        <div class="container rate-promises__grid">
            <article><AppIcon name="shield" :size="28" /><h3>{{ t('rates.quote') }}</h3><p>{{ t('rates.quoteText') }}</p></article>
            <article><AppIcon name="check" :size="28" /><h3>{{ t('rates.confirmed') }}</h3><p>{{ t('rates.confirmedText') }}</p></article>
            <article><AppIcon name="clock" :size="28" /><h3>{{ t('rates.available') }}</h3><p>{{ t('rates.availableText') }}</p></article>
        </div>
    </section>
    <PageBody :html="page.content_html" :eyebrow="t('rates.information')" :heading="t('rates.how')" />
</template>
