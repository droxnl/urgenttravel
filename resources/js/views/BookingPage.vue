<script setup>
import AppIcon from '../components/AppIcon.vue';
import BookingWizard from '../components/BookingWizard.vue';
import PageHero from '../components/PageHero.vue';
import { useI18n } from '../lib/i18n';

defineProps({ page: { type: Object, required: true } });
const { t } = useI18n();
</script>

<template>
    <PageHero
        :eyebrow="t('booking.reserve')"
        :title="t('booking.title')"
        :subtitle="t('booking.subtitle')"
        image="/images/uploads/WhatsApp-Image-2026-01-14-at-21.05.38-4.jpeg"
        compact
        :breadcrumbs="[{ label: t('booking.crumb') }]"
    />
    <section class="booking-page section">
        <div class="container booking-page__grid">
            <BookingWizard />
            <aside class="booking-aside">
                <span class="eyebrow">{{ t('booking.next') }}</span>
                <ol>
                    <li><span>01</span><div><strong>{{ t('booking.next1') }}</strong><p>{{ t('booking.next1Text') }}</p></div></li>
                    <li><span>02</span><div><strong>{{ t('booking.next2') }}</strong><p>{{ t('booking.next2Text') }}</p></div></li>
                    <li><span>03</span><div><strong>{{ t('booking.next3') }}</strong><p>{{ t('booking.next3Text') }}</p></div></li>
                    <li><span>04</span><div><strong>{{ t('booking.next4') }}</strong><p>{{ t('booking.next4Text') }}</p></div></li>
                </ol>
                <div class="booking-aside__promise">
                    <AppIcon name="shield" :size="24" />
                    <div><strong>{{ t('booking.promise') }}</strong><span>{{ t('booking.promiseText') }}</span></div>
                </div>
            </aside>
        </div>
    </section>
</template>
