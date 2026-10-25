<script setup>
import AppIcon from './AppIcon.vue';
import { sitePhoneLink, whatsappLink } from '../lib/seo';
import { useI18n } from '../lib/i18n';

const { t } = useI18n();
</script>

<template>
    <div class="floating-actions">
        <a class="floating-actions__wa" :href="whatsappLink(t('whatsapp.question'))" target="_blank" rel="noopener" :aria-label="t('whatsapp.message')">
            <AppIcon name="whatsapp" :size="23" />
        </a>
        <a class="floating-actions__phone" :href="sitePhoneLink()" :aria-label="t('nav.call')">
            <AppIcon name="phone" :size="21" />
        </a>
    </div>
</template>
