<script setup>
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from '../lib/i18n';

const route = useRoute();
const router = useRouter();
const { locale, alternatePath } = useI18n();

const flags = {
    en: '/images/flags/gb.svg',
    nl: '/images/flags/nl.svg',
};

function switchLanguage(target) {
    if (locale.value === target) return;
    router.push({
        path: alternatePath(route.path, target),
        query: route.query,
        hash: route.hash,
    });
}
</script>

<template>
    <div class="language-switcher" role="group" aria-label="Taal / Language">
        <button
            v-for="code in ['en', 'nl']"
            :key="code"
            type="button"
            class="language-switcher__button"
            :class="{ active: locale === code }"
            :aria-label="code === 'nl' ? 'Nederlands' : 'English'"
            :aria-pressed="locale === code"
            @click="switchLanguage(code)"
        >
            <img :src="flags[code]" alt="" width="24" height="24">
        </button>
    </div>
</template>
