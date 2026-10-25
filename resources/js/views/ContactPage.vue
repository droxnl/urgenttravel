<script setup>
import AppIcon from '../components/AppIcon.vue';
import BookingForm from '../components/BookingForm.vue';
import PageBody from '../components/PageBody.vue';
import PageHero from '../components/PageHero.vue';
import { siteState } from '../lib/seo';
import { useI18n } from '../lib/i18n';

defineProps({ page: { type: Object, required: true } });
const { locale, t } = useI18n();
</script>

<template>
    <PageHero
        :eyebrow="t('contact.eyebrow')"
        :title="t('contact.title')"
        :subtitle="t('contact.subtitle')"
        image="/images/uploads/WhatsApp-Image-2026-01-14-at-21.06.05-2.jpeg"
        compact
        :breadcrumbs="[{ label: t('contact.crumb') }]"
    />
    <section class="contact-section section">
        <div class="container contact-grid">
            <div class="contact-details">
                <span class="eyebrow">{{ t('contact.details') }}</span>
                <h2>{{ t('contact.speak') }}</h2>
                <a class="contact-detail" :href="`tel:${siteState.settings.phone || '+31641108800'}`">
                    <span><AppIcon name="phone" :size="21" /></span>
                    <div><small>{{ t('contact.call') }}</small><strong>{{ siteState.settings.phone || '+31 6 4110 8800' }}</strong></div>
                </a>
                <a class="contact-detail" :href="`mailto:${siteState.settings.email || 'info@urgent-travelservices.nl'}`">
                    <span><AppIcon name="mail" :size="21" /></span>
                    <div><small>{{ t('contact.email') }}</small><strong>{{ siteState.settings.email || 'info@urgent-travelservices.nl' }}</strong></div>
                </a>
                <div class="contact-detail">
                    <span><AppIcon name="pin" :size="21" /></span>
                    <div><small>{{ t('contact.address') }}</small><strong>{{ siteState.settings.address }}</strong><em>{{ siteState.settings.postcode }} {{ siteState.settings.city }}, {{ locale === 'nl' ? 'Nederland' : siteState.settings.country }}</em></div>
                </div>
                <div class="contact-company">
                    <span>KvK {{ siteState.settings.kvk }}</span>
                    <span>VAT {{ siteState.settings.vat }}</span>
                </div>
            </div>
            <BookingForm type="contact" />
        </div>
    </section>
    <PageBody :html="page.content_html" :eyebrow="t('contact.company')" :heading="t('contact.about')" compact />
</template>
