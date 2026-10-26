<script setup>
import { computed } from 'vue';
import AppIcon from '../components/AppIcon.vue';
import BlockRenderer from '../components/BlockRenderer.vue';
import PageBody from '../components/PageBody.vue';
import PageHero from '../components/PageHero.vue';
import SidebarBookingCard from '../components/SidebarBookingCard.vue';
import { useI18n } from '../lib/i18n';

const props = defineProps({ page: { type: Object, required: true } });
const { t, localizedPath } = useI18n();

const contentText = computed(() => {
    if (!props.page.content_html) return '';
    return new DOMParser().parseFromString(props.page.content_html, 'text/html').body.textContent.trim();
});

const hasContent = computed(() => contentText.value.length > 25);
const isLanding = computed(() => ['landing', 'service'].includes(props.page.template));
const breadcrumbs = computed(() => {
    if (props.page.template === 'service') {
        return [{ label: t('page.services'), to: localizedPath('/services/') }, { label: props.page.title }];
    }
    return [];
});
</script>

<template>
    <PageHero
        :eyebrow="page.hero_eyebrow"
        :title="page.hero_title"
        :subtitle="page.hero_subtitle"
        :image="page.hero_image"
        :breadcrumbs="breadcrumbs"
    >
        <div class="page-hero__actions">
            <RouterLink class="button button--gold" :to="localizedPath('/booking/')">{{ t('page.request') }} <AppIcon name="arrow" :size="17" /></RouterLink>
            <RouterLink class="button button--ghost" :to="localizedPath('/contact/')">{{ t('page.speak') }}</RouterLink>
        </div>
    </PageHero>

    <section class="page-intro section">
        <div class="container page-intro__grid" :class="{ 'page-intro__grid--single': !isLanding }">
            <div class="page-intro__copy">
                <span class="eyebrow">{{ t('page.ut') }}</span>
                <h2>{{ isLanding ? t('page.simpleJourney') : t('page.attention') }}</h2>
                <p>{{ page.seo_description || page.excerpt }}</p>
            </div>
            <SidebarBookingCard v-if="isLanding" />
        </div>
    </section>

    <PageBody
        v-if="hasContent"
        :html="page.content_html"
        :eyebrow="page.template === 'landing' ? t('page.transferGuide') : t('page.serviceDetails')"
        :heading="page.template === 'landing' ? t('page.schipholJourney') : t('page.howHelp')"
    />

    <section v-else class="journey-steps section">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">{{ t('page.stepsEyebrow') }}</span>
                    <h2>{{ t('page.stepsTitle1') }}<br><em>{{ t('page.stepsTitle2') }}</em></h2>
                </div>
                <p>{{ t('page.stepsIntro') }}</p>
            </div>
            <div class="steps-grid">
                <article><span>1</span><h3>{{ t('page.step1') }}</h3><p>{{ t('page.step1Text') }}</p></article>
                <article><span>2</span><h3>{{ t('page.step2') }}</h3><p>{{ t('page.step2Text') }}</p></article>
                <article><span>3</span><h3>{{ t('page.step3') }}</h3><p>{{ t('page.step3Text') }}</p></article>
            </div>
        </div>
    </section>

    <BlockRenderer v-for="block in page.blocks" :key="block.id || `${block.type}-${block.sort_order}`" :block="block" />
</template>
