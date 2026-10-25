<script setup>
import { useRouter } from 'vue-router';
import { useI18n } from '../lib/i18n';

defineProps({
    html: { type: String, default: '' },
    eyebrow: { type: String, default: '' },
    heading: { type: String, default: '' },
    compact: { type: Boolean, default: false },
});

const router = useRouter();
const { t, localizedPath } = useI18n();

function handleClick(event) {
    const link = event.target.closest('a');
    if (!link) return;
    const url = new URL(link.href, window.location.origin);
    if (url.origin !== window.location.origin) return;
    event.preventDefault();
    router.push(`${localizedPath(url.pathname)}${url.search}${url.hash}`);
}
</script>

<template>
    <section v-if="html" class="content-section" :class="{ 'content-section--compact': compact }" id="page-content">
        <div class="container content-layout">
            <aside class="content-rail">
                <span class="eyebrow">{{ eyebrow || t('page.details') }}</span>
                <span class="content-rail__line"></span>
                <h2>{{ heading || t('page.help') }}</h2>
            </aside>
            <article class="article-body" v-html="html" @click="handleClick"></article>
        </div>
    </section>
</template>
