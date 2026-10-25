<script setup>
import PageBody from '../components/PageBody.vue';
import PageHero from '../components/PageHero.vue';
import BlockRenderer from '../components/BlockRenderer.vue';
import { computed } from 'vue';
import { useI18n } from '../lib/i18n';

const props = defineProps({ page: { type: Object, required: true } });
const { t } = useI18n();
const serviceBlock = computed(() => props.page.blocks?.find((block) => block.type === 'service_grid'));
</script>

<template>
    <PageHero
        :eyebrow="page.hero_eyebrow"
        :title="page.hero_title"
        :subtitle="page.hero_subtitle"
        :image="page.hero_image"
        :breadcrumbs="[{ label: t('page.services') }]"
    />
    <BlockRenderer v-if="serviceBlock" :block="serviceBlock" />
    <PageBody :html="page.content_html" :eyebrow="t('serviceIndex.overview')" :heading="t('page.howHelp')" />
</template>
