<script setup>
import { computed } from 'vue';
import AppIcon from '../components/AppIcon.vue';
import BlockRenderer from '../components/BlockRenderer.vue';
import HeroBooker from '../components/HeroBooker.vue';
import PageBody from '../components/PageBody.vue';
import { usePage } from '../composables/usePage';
import { useI18n } from '../lib/i18n';
import { formatPhone, sitePhoneLink, whatsappLink } from '../lib/seo';

const { locale, t, localizedPath } = useI18n();
const { page, error } = usePage(() => (locale.value === 'nl' ? '/nl/' : '/'));
const heroImage = computed(() => page.value?.hero_image || '/images/uploads/WhatsApp-Image-2026-01-14-at-21.05.57.jpeg');
const aboutImage = '/images/uploads/WhatsApp-Image-2026-01-14-at-21.05.38-4.jpeg';
const signatureLogo = '/images/brand/urgent-travel-services-logo.png';
const blocks = computed(() => page.value?.blocks || []);
const blockByType = (type) => blocks.value.find((block) => block.type === type);
</script>

<template>
    <div v-if="error" class="error-page container">
        <span class="eyebrow">{{ t('error.generic') }}</span>
        <h1>{{ t('error.loading') }}</h1>
        <p>{{ error }}</p>
    </div>
    <template v-else-if="page">
        <section class="home-hero">
            <img class="home-hero__image" :src="heroImage" alt="Urgent Travel Services privéchauffeur" fetchpriority="high">
            <div class="home-hero__overlay"></div>
            <div class="home-hero__grid"></div>
            <div class="container home-hero__inner">
                <div class="home-hero__copy">
                    <div class="hero-meta">
                        <span class="hero-meta__line"></span>
                        <span>{{ t('home.meta') }}</span>
                    </div>
                    <span class="eyebrow eyebrow--gold">{{ t('home.eyebrow') }}</span>
                    <h1>{{ t('home.title1') }}<br>{{ t('home.title2') }}<br><em>{{ t('home.title3') }}</em></h1>
                    <p class="home-hero__lead">
                        {{ t('home.lead') }}
                    </p>
                    <div class="home-hero__actions">
                        <RouterLink class="button button--gold button--large" :to="localizedPath('/booking/')">
                            {{ t('home.book') }} <AppIcon name="arrow" :size="19" />
                        </RouterLink>
                        <a class="button button--ghost button--large" :href="sitePhoneLink()">
                            <AppIcon name="phone" :size="18" /> {{ formatPhone() }}
                        </a>
                    </div>
                    <div class="hero-assurance">
                        <span><AppIcon name="check" :size="15" /> {{ t('home.flight') }}</span>
                        <span><AppIcon name="check" :size="15" /> {{ t('home.quote') }}</span>
                        <span><AppIcon name="check" :size="15" /> {{ t('home.childSeats') }}</span>
                    </div>
                </div>
                <div class="home-hero__booker">
                    <HeroBooker />
                </div>
            </div>
            <a class="hero-wa" :href="whatsappLink(t('whatsapp.greeting'))" target="_blank" rel="noopener">
                <AppIcon name="whatsapp" :size="18" />
                <span>{{ t('whatsapp.urgent') }}<strong>{{ t('whatsapp.message') }}</strong></span>
            </a>
        </section>

        <BlockRenderer v-if="blockByType('trust_strip')" :block="blockByType('trust_strip')" />

        <section class="about-section section">
            <div class="container about-layout">
                <div class="about-visual">
                    <div class="about-visual__image">
                        <img :src="aboutImage" :alt="t('home.aboutLead')" loading="lazy">
                    </div>
                    <div class="experience-card">
                        <strong>20+</strong>
                        <span>{{ t('home.years') }}</span>
                    </div>
                    <span class="about-visual__accent"></span>
                </div>
                <div class="about-copy">
                    <span class="eyebrow">{{ t('home.aboutEyebrow') }}</span>
                    <h2>{{ t('home.aboutTitle1') }}<br><em>{{ t('home.aboutTitle2') }}</em></h2>
                    <p class="about-copy__lead">
                        {{ t('home.aboutLead') }}
                    </p>
                    <p>
                        {{ t('home.aboutBody') }}
                    </p>
                    <div class="about-signature">
                        <img :src="signatureLogo" class="about-signature__logo" alt="Urgent Travel Services">
                        <span><strong>Urgent Travel Services</strong><small>{{ t('home.aboutSignature') }}</small></span>
                    </div>
                    <RouterLink class="text-link" :to="localizedPath('/services/')">
                        {{ t('home.discover') }} <AppIcon name="arrow" :size="17" />
                    </RouterLink>
                </div>
            </div>
        </section>

        <BlockRenderer v-if="blockByType('service_grid')" :block="blockByType('service_grid')" />
        <BlockRenderer v-if="blockByType('fleet_grid')" :block="blockByType('fleet_grid')" />
        <BlockRenderer v-if="blockByType('coverage_grid')" :block="blockByType('coverage_grid')" />

        <PageBody
            :html="page.content_html"
            :eyebrow="t('home.completeGuide')"
            :heading="t('home.moreAbout')"
        />
    </template>
    <div v-else class="page-loading container">{{ t('home.loading') }}</div>
</template>
