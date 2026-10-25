<script setup>
import AppIcon from './AppIcon.vue';
import { cityGroups } from '../lib/content';
import { useI18n } from '../lib/i18n';

const { t, localizedPath } = useI18n();
</script>

<template>
    <section class="coverage-section section">
        <div class="container">
            <div class="coverage-heading">
                <div>
                    <span class="eyebrow eyebrow--gold">{{ t('coverage.eyebrow') }}</span>
                    <h2>{{ t('coverage.title1') }}<br><em>{{ t('coverage.title2') }}</em></h2>
                </div>
                <p>{{ t('coverage.intro') }}</p>
            </div>
            <div class="coverage-grid">
                <div v-for="group in cityGroups" :key="group.province" class="province-card">
                    <span class="province-card__index">{{ String(cityGroups.indexOf(group) + 1).padStart(2, '0') }}</span>
                    <h3>{{ group.province }}</h3>
                    <div class="province-card__cities">
                        <RouterLink v-for="[city, slug] in group.cities" :key="slug" :to="localizedPath(`/${slug}/`)">
                            {{ city }} <AppIcon name="arrow" :size="14" />
                        </RouterLink>
                    </div>
                </div>
            </div>
            <div class="coverage-footer">
                <span><AppIcon name="plane" :size="19" /> {{ t('coverage.quotes') }}</span>
                <RouterLink class="text-link" :to="localizedPath('/booking/')">{{ t('coverage.plan') }} <AppIcon name="arrow" :size="16" /></RouterLink>
            </div>
        </div>
    </section>
</template>
