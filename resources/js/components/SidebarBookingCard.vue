<script setup>
import AppIcon from './AppIcon.vue';
import { useI18n } from '../lib/i18n';
import { formatPhone, sitePhoneLink, whatsappLink } from '../lib/seo';

const { t, localizedPath } = useI18n();
</script>

<template>
    <aside class="sidebar-card">
        <span class="eyebrow eyebrow--gold">{{ t('sidebar.eyebrow') }}</span>
        <h2>{{ t('sidebar.title') }}</h2>
        <p>{{ t('sidebar.text') }}</p>
        <RouterLink class="button button--gold" :to="localizedPath('/booking/')">
            {{ t('sidebar.request') }} <AppIcon name="arrow" :size="17" />
        </RouterLink>
        <a class="sidebar-card__contact" :href="sitePhoneLink()">
            <AppIcon name="phone" :size="18" />
            <span><small>{{ t('sidebar.speak') }}</small>{{ formatPhone() }}</span>
        </a>
        <a class="sidebar-card__contact" :href="whatsappLink(t('whatsapp.question'))" target="_blank" rel="noopener">
            <AppIcon name="whatsapp" :size="18" />
            <span><small>{{ t('sidebar.question') }}</small>{{ t('sidebar.whatsapp') }}</span>
        </a>
    </aside>
</template>
