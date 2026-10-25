<script setup>
import AppIcon from './AppIcon.vue';
import { useI18n } from '../lib/i18n';

const { t, localizedPath } = useI18n();

defineProps({
    eyebrow: String,
    title: { type: String, required: true },
    subtitle: String,
    image: String,
    compact: { type: Boolean, default: false },
    breadcrumbs: { type: Array, default: () => [] },
});
</script>

<template>
    <section class="page-hero" :class="{ 'page-hero--compact': compact, 'page-hero--image': image }">
        <img v-if="image" class="page-hero__image" :src="image" alt="" fetchpriority="high">
        <div class="page-hero__shade"></div>
        <div class="page-hero__rail" aria-hidden="true"><span></span><span></span><span></span></div>
        <div class="container page-hero__content">
            <nav v-if="breadcrumbs.length" class="breadcrumbs" :aria-label="t('common.breadcrumb')">
                <RouterLink :to="localizedPath('/')">{{ t('page.home') }}</RouterLink>
                <template v-for="crumb in breadcrumbs" :key="crumb.label">
                    <span>/</span>
                    <RouterLink v-if="crumb.to" :to="crumb.to">{{ crumb.label }}</RouterLink>
                    <span v-else>{{ crumb.label }}</span>
                </template>
            </nav>
            <span v-if="eyebrow" class="eyebrow eyebrow--gold">{{ eyebrow }}</span>
            <h1>{{ title }}</h1>
            <p v-if="subtitle">{{ subtitle }}</p>
            <slot />
        </div>
        <RouterLink class="page-hero__down" to="#page-content" :aria-label="t('page.details')">
            <AppIcon name="chevron" :size="22" />
        </RouterLink>
    </section>
</template>
