<script setup>
import { useI18n } from '../lib/i18n';

defineProps({
    light: { type: Boolean, default: false },
});

const logoUrl = '/images/brand/urgent-travel-services-logo.png';
const { t, localizedPath } = useI18n();
</script>

<template>
    <RouterLink :to="localizedPath('/')" class="brand" :class="{ 'brand--light': light }" :aria-label="t('common.homeAria')">
        <img
            :src="logoUrl"
            class="brand__logo"
            alt="Urgent Travel Services"
            width="778"
            height="293"
            decoding="async"
        >
    </RouterLink>
</template>
