<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { api, validationMessage } from '../lib/api';
import { useI18n } from '../lib/i18n';
import AppIcon from './AppIcon.vue';

const props = defineProps({
    type: { type: String, default: 'booking' },
    heading: { type: String, default: '' },
    intro: { type: String, default: '' },
});

const route = useRoute();
const { t } = useI18n();
const today = computed(() => new Date().toISOString().slice(0, 10));
const busy = ref(false);
const error = ref('');
const success = ref(null);
const form = reactive({
    type: props.type,
    name: '',
    email: '',
    phone: '',
    pickup: '',
    dropoff: '',
    pickup_date: '',
    pickup_time: '',
    passengers: 2,
    luggage: 2,
    flight_number: '',
    service_type: 'Airport transfer',
    message: '',
    consent: false,
});

watch(
    () => route.query,
    (query) => {
        for (const key of ['pickup', 'dropoff', 'pickup_date', 'pickup_time', 'passengers', 'luggage', 'service_type']) {
            if (query[key]) form[key] = query[key];
        }
    },
    { immediate: true },
);

async function submit() {
    busy.value = true;
    error.value = '';
    success.value = null;
    try {
        success.value = await api.createInquiry({
            ...form,
            source_page: window.location.pathname,
            locale: document.documentElement.lang === 'nl' ? 'nl' : 'en',
        });
        for (const key of ['name', 'email', 'phone', 'flight_number', 'message']) form[key] = '';
        form.consent = false;
    } catch (exception) {
        error.value = validationMessage(exception);
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <section class="booking-form-card" aria-labelledby="booking-form-title">
        <div class="booking-form-card__header">
            <span class="eyebrow">{{ type === 'contact' ? t('form.contactEyebrow') : t('form.eyebrow') }}</span>
            <h2 id="booking-form-title">{{ heading || (type === 'contact' ? t('form.titleContact') : t('form.title')) }}</h2>
            <p>{{ intro || (type === 'contact' ? t('form.introContact') : t('form.intro')) }}</p>
        </div>

        <form class="booking-form" @submit.prevent="submit">
            <div v-if="type !== 'contact'" class="form-section">
                <h3><span>1</span> {{ t('form.journey') }}</h3>
                <div class="form-grid">
                    <label class="field field--wide">
                        <span>{{ t('form.pickup') }}</span>
                        <input v-model="form.pickup" required :placeholder="t('form.pickupPlaceholder')">
                    </label>
                    <label class="field field--wide">
                        <span>{{ t('form.dropoff') }}</span>
                        <input v-model="form.dropoff" required :placeholder="t('form.dropoffPlaceholder')">
                    </label>
                    <label class="field">
                        <span>{{ t('form.date') }}</span>
                        <input v-model="form.pickup_date" type="date" :min="today" required>
                    </label>
                    <label class="field">
                        <span>{{ t('form.time') }}</span>
                        <input v-model="form.pickup_time" type="time" required>
                    </label>
                    <label class="field">
                        <span>{{ t('form.service') }}</span>
                        <select v-model="form.service_type">
                            <option value="Airport transfer">{{ t('service.airport.title') }}</option>
                            <option value="Hourly chauffeur">{{ t('booker.hourly') }}</option>
                            <option value="Business travel">{{ t('service.business.title') }}</option>
                            <option value="Private chauffeur">{{ t('service.private.title') }}</option>
                            <option value="City to city">{{ t('service.city.title') }}</option>
                            <option value="Sightseeing">{{ t('service.sightseeing.title') }}</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>{{ t('form.flight') }}</span>
                        <input v-model="form.flight_number" :placeholder="t('form.optional')">
                    </label>
                    <label class="field">
                        <span>{{ t('form.passengers') }}</span>
                        <select v-model="form.passengers">
                            <option v-for="number in 16" :key="number" :value="number">{{ number }}</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>{{ t('form.luggage') }}</span>
                        <select v-model="form.luggage">
                            <option v-for="number in 25" :key="number - 1" :value="number - 1">{{ number - 1 }}</option>
                        </select>
                    </label>
                </div>
            </div>
            <div class="form-section">
                <h3><span>2</span> {{ t('form.details') }}</h3>
                <div class="form-grid">
                    <label class="field">
                        <span>{{ t('form.name') }}</span>
                        <input v-model="form.name" required autocomplete="name" :placeholder="t('form.namePlaceholder')">
                    </label>
                    <label class="field">
                        <span>{{ t('form.email') }}</span>
                        <input v-model="form.email" type="email" required autocomplete="email" placeholder="you@example.com">
                    </label>
                    <label class="field field--wide">
                        <span>{{ t('form.phone') }}</span>
                        <input v-model="form.phone" type="tel" required autocomplete="tel" placeholder="+31 6 1234 5678">
                    </label>
                    <label class="field field--wide">
                        <span>{{ t('form.message') }}</span>
                        <textarea v-model="form.message" rows="4" :placeholder="t('form.messagePlaceholder')"></textarea>
                    </label>
                </div>
            </div>
            <label class="checkbox-field">
                <input v-model="form.consent" type="checkbox" required>
                <span>{{ t('form.consent') }}</span>
            </label>
            <p v-if="error" class="form-alert form-alert--error" role="alert">{{ error }}</p>
            <div v-if="success" class="form-alert form-alert--success" role="status">
                <AppIcon name="check" :size="20" />
                <div>
                    <strong>{{ t('form.success') }}</strong>
                    <span>{{ t('form.reference') }} {{ success.reference }}</span>
                </div>
            </div>
            <button class="button button--gold button--large booking-form__submit" type="submit" :disabled="busy">
                {{ busy ? t('form.send') : type === 'contact' ? t('form.sendContact') : t('form.submit') }}
                <AppIcon v-if="!busy" name="arrow" :size="18" />
            </button>
            <p class="form-disclaimer">{{ t('form.disclaimer') }}</p>
        </form>
    </section>
</template>
