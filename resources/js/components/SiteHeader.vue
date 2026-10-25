<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import AppIcon from './AppIcon.vue';
import LanguageSwitcher from './LanguageSwitcher.vue';
import SiteLogo from './SiteLogo.vue';
import { useI18n } from '../lib/i18n';
import { formatPhone, sitePhoneLink, whatsappLink } from '../lib/seo';

const open = ref(false);
const scrolled = ref(false);
const { t, localizedPath } = useI18n();

function onScroll() {
    scrolled.value = window.scrollY > 24;
}

onMounted(() => {
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
});
onUnmounted(() => window.removeEventListener('scroll', onScroll));
</script>

<template>
    <header class="site-header" :class="{ 'site-header--scrolled': scrolled }">
        <div class="topline">
            <span>{{ t('topline.private') }}</span>
            <span class="topline__right"><i></i> {{ t('topline.available') }}</span>
        </div>
        <div class="header-main">
            <SiteLogo light />
            <nav class="desktop-nav" :aria-label="t('nav.main')">
                <RouterLink :to="localizedPath('/')">{{ t('nav.home') }}</RouterLink>
                <RouterLink :to="localizedPath('/services/')">{{ t('nav.services') }}</RouterLink>
                <RouterLink :to="localizedPath('/taxi-naar-schiphol/')">{{ t('nav.schiphol') }}</RouterLink>
                <RouterLink :to="localizedPath('/fleet/')">{{ t('nav.fleet') }}</RouterLink>
                <RouterLink :to="localizedPath('/rates/')">{{ t('nav.rates') }}</RouterLink>
                <RouterLink :to="localizedPath('/contact/')">{{ t('nav.contact') }}</RouterLink>
            </nav>
            <div class="header-actions">
                <a class="header-phone" :href="sitePhoneLink()">
                    <AppIcon name="phone" :size="18" />
                    <span><small>{{ t('nav.call') }}</small>{{ formatPhone() }}</span>
                </a>
                <LanguageSwitcher />
                <a class="button button--wa header-wa" :href="whatsappLink(t('whatsapp.greeting'))" target="_blank" rel="noopener">
                    <AppIcon name="whatsapp" :size="18" />
                    WhatsApp
                </a>
                <RouterLink class="button button--gold" :to="localizedPath('/booking/')">{{ t('nav.book') }}</RouterLink>
                <button class="mobile-toggle" :aria-expanded="open" :aria-label="t('nav.menu')" @click="open = !open">
                    <AppIcon :name="open ? 'x' : 'menu'" :size="26" />
                </button>
            </div>
        </div>
        <Transition name="mobile-menu">
            <nav v-if="open" class="mobile-nav" :aria-label="t('nav.menu')">
                <div class="mobile-nav__language"><LanguageSwitcher /></div>
                <RouterLink :to="localizedPath('/')" @click="open = false">{{ t('nav.home') }}</RouterLink>
                <RouterLink :to="localizedPath('/services/')" @click="open = false">{{ t('nav.services') }}</RouterLink>
                <RouterLink :to="localizedPath('/taxi-naar-schiphol/')" @click="open = false">{{ t('nav.schipholLong') }}</RouterLink>
                <RouterLink :to="localizedPath('/fleet/')" @click="open = false">{{ t('nav.fleet') }}</RouterLink>
                <RouterLink :to="localizedPath('/rates/')" @click="open = false">{{ t('nav.rates') }}</RouterLink>
                <RouterLink :to="localizedPath('/contact/')" @click="open = false">{{ t('nav.contact') }}</RouterLink>
                <RouterLink class="button button--gold" :to="localizedPath('/booking/')" @click="open = false">{{ t('nav.book') }}</RouterLink>
            </nav>
        </Transition>
    </header>
</template>
