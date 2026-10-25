<script setup>
import BlockRenderer from '../components/BlockRenderer.vue';
import PageBody from '../components/PageBody.vue';
import PageHero from '../components/PageHero.vue';
import { useI18n } from '../lib/i18n';
import { computed } from 'vue';

const props = defineProps({ page: { type: Object, required: true } });
const { t } = useI18n();
const fleetBlock = computed(() => props.page.blocks?.find((block) => block.type === 'fleet_grid'));
</script>

<template>
    <PageHero
        :eyebrow="t('fleetPage.eyebrow')"
        :title="page.hero_title"
        :subtitle="page.hero_subtitle"
        :image="page.hero_image"
        :breadcrumbs="[{ label: t('nav.fleet') }]"
    />
    <BlockRenderer v-if="fleetBlock" :block="fleetBlock" />
    <PageBody :html="page.content_html" :eyebrow="t('fleetPage.information')" :heading="t('fleetPage.every')" />
</template>
