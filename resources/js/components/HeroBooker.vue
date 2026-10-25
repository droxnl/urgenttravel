<script setup>
import { computed, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import AddressAutocomplete from './AddressAutocomplete.vue';
import AppIcon from './AppIcon.vue';
import { useI18n } from '../lib/i18n';

const router = useRouter();
const { t, localizedPath } = useI18n();
const mode = ref('transfer');
const today = computed(() => {
    const date = new Date();
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return date.toISOString().slice(0, 10);
});
const form = reactive({
    booking_mode: 'transfer',
    trip_type: 'one_way',
    pickup: '',
    dropoff: '',
    pickup_date: '',
    pickup_time: '',
    passengers: 2,
    luggage: 2,
    hourly_duration_hours: 3,
});

function submit() {
    router.push({
        path: localizedPath('/booking/'),
        query: {
            ...form,
            booking_mode: mode.value,
        },
    });
}
</script>

<template>
    <form class="hero-booker" @submit.prevent="submit">
        <div class="hero-booker__head">
            <div>
                <span class="eyebrow">{{ t('booker.eyebrow') }}</span>
                <h2>{{ t('booker.title') }}</h2>
            </div>
            <span class="hero-booker__badge">24/7</span>
        </div>
        <div class="booker-tabs" role="tablist" :aria-label="t('booker.journeyType')">
            <button type="button" :class="{ active: mode === 'transfer' }" :aria-selected="mode === 'transfer'" @click="mode = 'transfer'">
                {{ t('booker.oneWay') }}
            </button>
            <button type="button" :class="{ active: mode === 'hourly' }" :aria-selected="mode === 'hourly'" @click="mode = 'hourly'">
                {{ t('booker.hourly') }}
            </button>
        </div>
        <div class="booker-grid">
            <AddressAutocomplete
                v-model="form.pickup"
                class="booker-field booker-field--wide"
                :label="t('booker.pickup')"
                :placeholder="t('booker.pickupPlaceholder')"
                name="pickup"
                icon="pin"
                required
            />
            <AddressAutocomplete
                v-model="form.dropoff"
                class="booker-field booker-field--wide"
                :label="mode === 'hourly' ? t('booker.hourlyPreference') : t('booker.dropoff')"
                :placeholder="mode === 'hourly' ? t('booker.hourlyPlaceholder') : t('booker.dropoffPlaceholder')"
                name="dropoff"
                :icon="mode === 'hourly' ? 'clock' : 'plane'"
                :required="mode === 'transfer'"
            />
            <label class="booker-field">
                <span>{{ t('booker.date') }}</span>
                <input v-model="form.pickup_date" type="date" name="pickup_date" :min="today" required>
            </label>
            <label class="booker-field">
                <span>{{ t('booker.time') }}</span>
                <input v-model="form.pickup_time" type="time" name="pickup_time" required>
            </label>
            <label v-if="mode === 'transfer'" class="booker-field">
                <span>{{ t('booker.tripType') }}</span>
                <select v-model="form.trip_type" name="trip_type">
                    <option value="one_way">{{ t('booker.oneWay') }}</option>
                    <option value="return">{{ t('booker.return') }}</option>
                </select>
            </label>
            <label v-else class="booker-field">
                <span>{{ t('booker.hourlyDuration') }}</span>
                <select v-model.number="form.hourly_duration_hours" name="hourly_duration_hours">
                    <option v-for="hour in 12" :key="hour" :value="hour">{{ hour }} {{ hour === 1 ? t('booker.hour') : t('booker.hours') }}</option>
                </select>
            </label>
            <label class="booker-field">
                <span>{{ t('booker.passengers') }}</span>
                <select v-model="form.passengers" name="passengers">
                    <option v-for="number in 8" :key="number" :value="number">{{ number }}</option>
                </select>
            </label>
            <label class="booker-field">
                <span>{{ t('booker.luggage') }}</span>
                <select v-model="form.luggage" name="luggage">
                    <option v-for="number in 9" :key="number - 1" :value="number - 1">{{ number - 1 }}</option>
                </select>
            </label>
        </div>
        <button class="button button--gold hero-booker__submit" type="submit">
            {{ t('booker.continue') }}
            <AppIcon name="arrow" :size="18" />
        </button>
        <div class="hero-booker__notes">
            <span><AppIcon name="check" :size="14" /> {{ t('booker.noPayment') }}</span>
            <span><AppIcon name="check" :size="14" /> {{ t('booker.confirmation') }}</span>
        </div>
    </form>
</template>
