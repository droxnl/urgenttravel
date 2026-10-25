<script setup>
import { computed } from 'vue';
import AppIcon from './AppIcon.vue';
import { getServices } from '../lib/content';
import { useI18n } from '../lib/i18n';

const { locale, t, localizedPath } = useI18n();
const services = computed(() => getServices(locale.value));
</script>

<template>
    <section class="services-section section">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">{{ t('services.eyebrow') }}</span>
                    <h2>{{ t('services.title1') }}<br><em>{{ t('services.title2') }}</em></h2>
                </div>
                <p>{{ t('services.intro') }}</p>
            </div>
            <div class="service-grid">
                <RouterLink v-for="(service, index) in services" :key="service.slug" :to="localizedPath(`/${service.slug}/`)" class="service-card">
                    <span class="service-card__number">{{ String(index + 1).padStart(2, '0') }}</span>
                    <span class="service-card__icon"><AppIcon :name="service.icon" :size="26" /></span>
                    <span class="service-card__kicker">{{ service.kicker }}</span>
                    <h3>{{ service.title }}</h3>
                    <p>{{ service.description }}</p>
                    <span class="service-card__link">{{ t('services.explore') }} <AppIcon name="arrow" :size="17" /></span>
                </RouterLink>
            </div>
        </div>
    </section>
</template>
