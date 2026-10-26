<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import AddressAutocomplete from './AddressAutocomplete.vue';
import AppIcon from './AppIcon.vue';
import RouteMap from './RouteMap.vue';
import { api, validationMessage } from '../lib/api';
import { useI18n } from '../lib/i18n';

const route = useRoute();
const { locale, t } = useI18n();
const today = computed(() => {
    const date = new Date();
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return date.toISOString().slice(0, 10);
});
const step = ref(1);
const loading = ref(false);
const quoting = ref(false);
const submitting = ref(false);
const error = ref('');
const success = ref(null);
const quote = ref(null);
const vehicles = ref([]);
const extras = ref([]);
const selectedVehicleId = ref(null);
const activeCategory = ref('all');
const extraQuantities = reactive({});

const journey = reactive({
    booking_mode: 'transfer',
    trip_type: 'one_way',
    pickup: '',
    dropoff: '',
    pickup_date: '',
    pickup_time: '',
    passengers: 2,
    luggage: 2,
    flight_number: '',
    hourly_duration_hours: 3,
});

const customer = reactive({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    invoice_address: '',
    message: '',
    payment_method: 'card',
    consent: false,
});

const steps = computed(() => [
    { number: 1, label: t('bookingFlow.stepRoute') },
    { number: 2, label: t('bookingFlow.stepMap') },
    { number: 3, label: t('bookingFlow.stepVehicle') },
    { number: 4, label: t('bookingFlow.stepDetails') },
    { number: 5, label: t('bookingFlow.stepReview') },
]);

const categories = computed(() => [
    { key: 'all', label: t('bookingFlow.categoryAll') },
    { key: 'comfort', label: t('bookingFlow.categoryComfort') },
    { key: 'business', label: t('bookingFlow.categoryBusiness') },
    { key: 'van', label: t('bookingFlow.categoryVan') },
    { key: 'luxury', label: t('bookingFlow.categoryLuxury') },
]);

const passengerLimit = computed(() => Math.max(8, ...vehicles.value.map((vehicle) => vehicle.max_passengers)));
const luggageLimit = computed(() => Math.max(8, ...vehicles.value.map((vehicle) => vehicle.max_luggage)));

const visibleVehicles = computed(() => vehicles.value.filter(
    (vehicle) => activeCategory.value === 'all' || vehicle.category === activeCategory.value,
));

const selectedVehicle = computed(() => vehicles.value.find(
    (vehicle) => vehicle.id === Number(selectedVehicleId.value),
));

const selectedExtras = computed(() => extras.value
    .map((extra) => ({
        ...extra,
        quantity: Number(extraQuantities[extra.key] || 0),
    }))
    .filter((extra) => extra.quantity > 0));

const extrasTotal = computed(() => selectedExtras.value.reduce((total, extra) => {
    const billed = extra.pricing_type === 'per_booking' ? 1 : extra.quantity;
    return total + Number(extra.price) * billed;
}, 0));

const estimatedTotal = computed(() => {
    if (!selectedVehicle.value || selectedVehicle.value.estimated_total === null) return null;
    return Number(selectedVehicle.value.estimated_total) + extrasTotal.value;
});

const billedDistance = computed(() => {
    if (!quote.value?.route) return 0;
    return Number(quote.value.route.distance_km) * (journey.trip_type === 'return' ? 2 : 1);
});

function formatMoney(value) {
    if (value === null || value === undefined) return t('bookingFlow.priceRequest');
    return new Intl.NumberFormat(locale.value === 'nl' ? 'nl-NL' : 'en-GB', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2,
    }).format(Number(value));
}

function formatDistance(value) {
    return new Intl.NumberFormat(locale.value === 'nl' ? 'nl-NL' : 'en-GB', {
        maximumFractionDigits: 1,
    }).format(Number(value || 0));
}

function formatDate(value) {
    if (!value) return '';
    return new Intl.DateTimeFormat(locale.value === 'nl' ? 'nl-NL' : 'en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(`${value}T12:00:00`));
}

function setExtra(extra, quantity) {
    extraQuantities[extra.key] = Math.max(0, Math.min(Number(quantity), extra.max_quantity));
}

function toggleFixedExtra(extra) {
    setExtra(extra, extraQuantities[extra.key] ? 0 : 1);
}

function chooseVehicle(vehicle) {
    if (!vehicle.available) return;
    selectedVehicleId.value = vehicle.id;
}

function resetVehicles() {
    selectedVehicleId.value = null;
    quote.value = null;
    vehicles.value = [];
    extras.value = [];
    activeCategory.value = 'all';
    for (const key of Object.keys(extraQuantities)) delete extraQuantities[key];
}

function setMode(mode) {
    if (journey.booking_mode === mode) return;
    journey.booking_mode = mode;
    if (mode === 'hourly') journey.trip_type = 'one_way';
    resetVehicles();
}

function validateJourney() {
    if (!journey.pickup.trim()) return t('bookingFlow.requiredRoute');
    if (journey.booking_mode === 'transfer' && !journey.dropoff.trim()) return t('bookingFlow.requiredRoute');
    if (!journey.pickup_date || !journey.pickup_time) return t('bookingFlow.requiredRoute');
    if (journey.booking_mode === 'hourly' && !journey.hourly_duration_hours) return t('bookingFlow.requiredHourly');
    return '';
}

async function calculateRoute() {
    error.value = validateJourney();
    if (error.value) return;

    quoting.value = true;
    quote.value = null;
    vehicles.value = [];
    selectedVehicleId.value = null;
    try {
        const result = await api.getBookingQuote({
            booking_mode: journey.booking_mode,
            trip_type: journey.booking_mode === 'hourly' ? 'one_way' : journey.trip_type,
            pickup: journey.pickup,
            dropoff: journey.booking_mode === 'hourly' ? null : journey.dropoff || null,
            passengers: journey.passengers,
            luggage: journey.luggage,
            hourly_duration_hours: journey.booking_mode === 'hourly' ? journey.hourly_duration_hours : null,
            locale: locale.value,
        });
        quote.value = result;
        vehicles.value = result.vehicles || [];
        extras.value = result.extras || [];
        for (const extra of extras.value) {
            if (!(extra.key in extraQuantities)) extraQuantities[extra.key] = 0;
        }
        step.value = 2;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (exception) {
        error.value = validationMessage(exception) || t('bookingFlow.routeError');
    } finally {
        quoting.value = false;
    }
}

function showVehicles() {
    if (!quote.value || !vehicles.value.length) return;
    step.value = 3;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function continueToDetails() {
    error.value = selectedVehicle.value
        ? ''
        : t('bookingFlow.requiredVehicle');
    if (error.value) return;
    step.value = 4;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function continueToReview() {
    if (!customer.first_name.trim() || !customer.last_name.trim() || !customer.email.trim() || !customer.phone.trim() || !customer.payment_method) {
        error.value = t('bookingFlow.requiredDetails');
        return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customer.email)) {
        error.value = t('bookingFlow.invalidEmail');
        return;
    }
    if (!customer.consent) {
        error.value = t('bookingFlow.requiredConsent');
        return;
    }
    error.value = '';
    step.value = 5;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function goToStep(target) {
    if (target < step.value) {
        error.value = '';
        step.value = target;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

async function submitBooking() {
    submitting.value = true;
    error.value = '';
    try {
        const extrasPayload = Object.fromEntries(
            selectedExtras.value.map((extra) => [extra.key, extra.quantity]),
        );
        const result = await api.createInquiry({
            type: 'booking',
            name: `${customer.first_name.trim()} ${customer.last_name.trim()}`,
            email: customer.email,
            phone: customer.phone,
            pickup: journey.pickup,
            dropoff: journey.dropoff || null,
            pickup_date: journey.pickup_date,
            pickup_time: journey.pickup_time,
            passengers: journey.passengers,
            luggage: journey.luggage,
            flight_number: journey.flight_number || null,
            service_type: journey.booking_mode === 'hourly' ? 'Hourly chauffeur' : 'Airport transfer',
            vehicle_id: selectedVehicle.value.id,
            booking_mode: journey.booking_mode,
            trip_type: journey.booking_mode === 'hourly' ? 'one_way' : journey.trip_type,
            route_distance_km: quote.value.route.distance_km,
            route_duration_minutes: quote.value.route.duration_minutes,
            hourly_duration_hours: journey.booking_mode === 'hourly' ? journey.hourly_duration_hours : null,
            extras: extrasPayload,
            payment_method: customer.payment_method,
            invoice_address: customer.invoice_address || null,
            message: customer.message || null,
            source_page: window.location.pathname,
            locale: locale.value,
            consent: customer.consent,
        });
        success.value = result;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (exception) {
        error.value = validationMessage(exception);
    } finally {
        submitting.value = false;
    }
}

function startAgain() {
    success.value = null;
    step.value = 1;
    resetVehicles();
    journey.pickup = '';
    journey.dropoff = '';
    journey.pickup_date = '';
    journey.pickup_time = '';
    journey.flight_number = '';
    customer.first_name = '';
    customer.last_name = '';
    customer.email = '';
    customer.phone = '';
    customer.invoice_address = '';
    customer.message = '';
    customer.consent = false;
}

async function loadCatalog() {
    loading.value = true;
    try {
        const result = await api.getBookingConfig(locale.value);
        if (!quote.value) {
            vehicles.value = result.vehicles || [];
            extras.value = result.extras || [];
        }
    } catch (exception) {
        error.value = validationMessage(exception);
    } finally {
        loading.value = false;
    }
}

watch(
    () => route.query,
    (query) => {
        if (!query || !Object.keys(query).length) return;
        journey.booking_mode = query.booking_mode === 'hourly' ? 'hourly' : 'transfer';
        journey.trip_type = query.trip_type === 'return' ? 'return' : 'one_way';
        for (const key of ['pickup', 'dropoff', 'pickup_date', 'pickup_time', 'passengers', 'luggage', 'hourly_duration_hours', 'flight_number']) {
            if (query[key] !== undefined && query[key] !== '') {
                journey[key] = ['passengers', 'luggage', 'hourly_duration_hours'].includes(key)
                    ? Number(query[key])
                    : query[key];
            }
        }
    },
    { immediate: true },
);

watch(locale, () => {
    step.value = 1;
    resetVehicles();
    loadCatalog();
});

watch(
    () => [
        journey.booking_mode,
        journey.trip_type,
        journey.pickup,
        journey.dropoff,
        journey.pickup_date,
        journey.pickup_time,
        journey.passengers,
        journey.luggage,
        journey.hourly_duration_hours,
    ],
    () => {
        if (quote.value && !quoting.value) resetVehicles();
    },
);

onMounted(async () => {
    await loadCatalog();
    const hasQueryRoute = route.query.pickup
        && route.query.pickup_date
        && route.query.pickup_time
        && (journey.booking_mode === 'hourly' || route.query.dropoff);
    if (hasQueryRoute) await calculateRoute();
});
</script>

<template>
    <section class="booking-wizard" :aria-label="t('bookingFlow.title')">
        <template v-if="!success">
            <nav class="booking-steps" :aria-label="t('bookingFlow.title')">
                <button
                    v-for="item in steps"
                    :key="item.number"
                    type="button"
                    :class="{ active: step === item.number, complete: step > item.number }"
                    :aria-current="step === item.number ? 'step' : undefined"
                    :aria-label="`${item.number}. ${item.label}`"
                    :disabled="item.number >= step"
                    @click="goToStep(item.number)"
                >
                    <span>{{ step > item.number ? '✓' : item.number }}</span>
                    <strong>{{ item.label }}</strong>
                </button>
            </nav>

            <p v-if="error" class="form-alert form-alert--error" role="alert">{{ error }}</p>

            <div v-if="loading" class="booking-wizard__loading">
                <span class="loading-line"></span>
                <p>{{ t('bookingFlow.calculating') }}</p>
            </div>

            <Transition v-else name="wizard-step" mode="out-in">
                <div v-if="step === 1" key="journey" class="wizard-panel">
                    <div class="wizard-panel__heading">
                        <span>1</span>
                        <div><h3>{{ t('bookingFlow.stepRoute') }}</h3><p>{{ journey.booking_mode === 'hourly' ? t('bookingFlow.hourlyText') : t('bookingFlow.transferText') }}</p></div>
                    </div>

                    <div class="wizard-mode">
                        <button type="button" :class="{ active: journey.booking_mode === 'transfer' }" :aria-pressed="journey.booking_mode === 'transfer'" @click="setMode('transfer')">
                            <AppIcon name="route" :size="19" />
                            <span><strong>{{ t('bookingFlow.transfer') }}</strong><small>{{ t('bookingFlow.transferText') }}</small></span>
                        </button>
                        <button type="button" :class="{ active: journey.booking_mode === 'hourly' }" :aria-pressed="journey.booking_mode === 'hourly'" @click="setMode('hourly')">
                            <AppIcon name="clock" :size="19" />
                            <span><strong>{{ t('bookingFlow.hourly') }}</strong><small>{{ t('bookingFlow.hourlyText') }}</small></span>
                        </button>
                    </div>

                    <div class="wizard-route-fields">
                        <AddressAutocomplete
                            v-model="journey.pickup"
                            class="field field--wide"
                            :label="t('bookingFlow.pickup')"
                            :placeholder="t('bookingFlow.pickupPlaceholder')"
                            name="pickup"
                            icon="pin"
                            required
                        />
                        <AddressAutocomplete
                            v-model="journey.dropoff"
                            class="field field--wide"
                            :label="journey.booking_mode === 'hourly' ? t('bookingFlow.hourlyArea') : t('bookingFlow.dropoff')"
                            :placeholder="journey.booking_mode === 'hourly' ? t('bookingFlow.hourlyPlaceholder') : t('bookingFlow.dropoffPlaceholder')"
                            :name="journey.booking_mode === 'hourly' ? 'area' : 'dropoff'"
                            :icon="journey.booking_mode === 'hourly' ? 'compass' : 'plane'"
                            :required="journey.booking_mode === 'transfer'"
                        />
                        <label class="field"><span>{{ t('bookingFlow.date') }} *</span><input v-model="journey.pickup_date" type="date" :min="today" required></label>
                        <label class="field"><span>{{ t('bookingFlow.time') }} *</span><input v-model="journey.pickup_time" type="time" required></label>
                        <label v-if="journey.booking_mode === 'transfer'" class="field">
                            <span>{{ t('bookingFlow.tripType') }}</span>
                            <select v-model="journey.trip_type"><option value="one_way">{{ t('bookingFlow.oneWay') }}</option><option value="return">{{ t('bookingFlow.return') }}</option></select>
                        </label>
                        <label v-else class="field">
                            <span>{{ t('bookingFlow.hours') }}</span>
                            <select v-model.number="journey.hourly_duration_hours"><option v-for="hour in 12" :key="hour" :value="hour">{{ hour }} {{ hour === 1 ? t('bookingFlow.hour') : t('bookingFlow.hoursPlural') }}</option></select>
                        </label>
                        <label class="field">
                            <span>{{ t('bookingFlow.passengers') }}</span>
                            <select v-model.number="journey.passengers"><option v-for="number in passengerLimit" :key="number" :value="number">{{ number }}</option></select>
                        </label>
                        <label class="field">
                            <span>{{ t('bookingFlow.luggage') }}</span>
                            <select v-model.number="journey.luggage"><option v-for="number in luggageLimit + 1" :key="number - 1" :value="number - 1">{{ number - 1 }}</option></select>
                        </label>
                        <label class="field field--wide">
                            <span>{{ t('bookingFlow.flight') }} <small>{{ t('bookingFlow.optional') }}</small></span>
                            <input v-model="journey.flight_number" placeholder="KL1234">
                        </label>
                    </div>

                    <button class="button button--gold button--large wizard-next" type="button" :disabled="quoting" @click="calculateRoute">
                        {{ quoting ? t('bookingFlow.calculating') : t('bookingFlow.calculate') }}
                        <AppIcon v-if="!quoting" name="arrow" :size="18" />
                    </button>
                </div>

                <div v-else-if="step === 2" key="map" class="wizard-panel wizard-route-panel">
                    <div class="wizard-panel__heading wizard-panel__heading--split">
                        <span>2</span>
                        <div><h3>{{ t('bookingFlow.stepMap') }}</h3><p>{{ t('bookingFlow.mapStepText') }}</p></div>
                        <button type="button" class="text-link" @click="step = 1">{{ t('bookingFlow.changeJourney') }}</button>
                    </div>

                    <div class="journey-ribbon">
                        <div><span>{{ journey.pickup }}</span><AppIcon name="arrow" :size="17" /><span>{{ journey.dropoff || t('bookingFlow.hourlyArea') }}</span></div>
                        <div><strong>{{ formatDate(journey.pickup_date) }}</strong><span>{{ journey.pickup_time }}</span></div>
                    </div>

                    <div v-if="quote" class="route-result">
                        <div class="route-result__facts">
                            <div v-if="journey.booking_mode === 'hourly'"><span class="eyebrow">{{ t('bookingFlow.routeReady') }}</span><strong>{{ journey.hourly_duration_hours }} {{ journey.hourly_duration_hours === 1 ? t('bookingFlow.hour') : t('bookingFlow.hoursPlural') }}</strong><small>{{ t('bookingFlow.hourly') }}</small></div>
                            <div v-else><span class="eyebrow">{{ t('bookingFlow.routeReady') }}</span><strong>{{ formatDistance(quote.route.distance_km) }} {{ t('bookingFlow.km') }}</strong><small>{{ t('bookingFlow.distance') }}</small></div>
                            <div v-if="journey.booking_mode === 'transfer'"><strong>{{ quote.route.duration_minutes || '—' }} {{ t('bookingFlow.minutes') }}</strong><small>{{ t('bookingFlow.duration') }}</small></div>
                        </div>
                        <RouteMap
                            :route="quote.route"
                            :labels="{
                                pickup: t('bookingFlow.pickup'),
                                dropoff: t('bookingFlow.dropoff'),
                                map: t('bookingFlow.routeReady'),
                                unavailable: t('bookingFlow.mapUnavailable'),
                            }"
                        />
                    </div>

                    <div class="wizard-actions wizard-route-actions">
                        <button type="button" class="button button--ghost wizard-back" @click="step = 1">{{ t('bookingFlow.back') }}</button>
                        <button class="button button--gold button--large" type="button" @click="showVehicles">
                            {{ t('bookingFlow.viewVehicles') }} <AppIcon name="arrow" :size="18" />
                        </button>
                    </div>
                </div>

                <div v-else-if="step === 3" key="vehicle" class="wizard-panel">
                    <div class="wizard-panel__heading wizard-panel__heading--split">
                        <span>3</span>
                        <div><h3>{{ t('bookingFlow.chooseVehicle') }}</h3><p>{{ t('bookingFlow.chooseVehicleText', { passengers: journey.passengers, luggage: journey.luggage }) }}</p></div>
                        <button type="button" class="text-link" @click="step = 1">{{ t('bookingFlow.changeJourney') }}</button>
                    </div>

                    <div class="journey-ribbon">
                        <div><span>{{ quote.route.pickup.label }}</span><AppIcon name="arrow" :size="17" /><span>{{ quote.route.dropoff?.label || journey.dropoff || t('bookingFlow.hourlyArea') }}</span></div>
                        <div><strong>{{ journey.booking_mode === 'hourly' ? `${journey.hourly_duration_hours} ${t('bookingFlow.hoursPlural')}` : `${formatDistance(quote.route.distance_km)} ${t('bookingFlow.km')}` }}</strong><span>{{ formatDate(journey.pickup_date) }} · {{ journey.pickup_time }}</span></div>
                    </div>

                    <div class="vehicle-category-filter" role="group" :aria-label="t('bookingFlow.categoryAll')">
                        <button v-for="category in categories" :key="category.key" type="button" :class="{ active: activeCategory === category.key }" @click="activeCategory = category.key">{{ category.label }}</button>
                    </div>

                    <div class="vehicle-selector">
                        <article
                            v-for="vehicle in visibleVehicles"
                            :key="vehicle.id"
                            class="vehicle-choice"
                            :class="{ selected: selectedVehicleId === vehicle.id, unavailable: !vehicle.available }"
                        >
                            <button type="button" :disabled="!vehicle.available" :aria-pressed="selectedVehicleId === vehicle.id" @click="chooseVehicle(vehicle)">
                                <div class="vehicle-choice__image">
                                    <img v-if="vehicle.image_url" :src="vehicle.image_url" :alt="vehicle.name" loading="lazy">
                                    <span v-if="vehicle.badge">{{ vehicle.badge }}</span>
                                </div>
                                <div class="vehicle-choice__body">
                                    <div class="vehicle-choice__title"><h4>{{ vehicle.name }}</h4><span v-if="selectedVehicleId === vehicle.id" class="vehicle-choice__check"><AppIcon name="check" :size="15" /> {{ t('bookingFlow.selected') }}</span></div>
                                    <p>{{ vehicle.description }}</p>
                                    <div class="vehicle-choice__capacity">
                                        <span><AppIcon name="user" :size="15" /> {{ vehicle.max_passengers }} {{ t('bookingFlow.people') }}</span>
                                        <span><AppIcon name="luggage" :size="15" /> {{ vehicle.max_luggage }} {{ t('bookingFlow.bags') }}</span>
                                    </div>
                                </div>
                                <div class="vehicle-choice__price">
                                    <template v-if="journey.booking_mode === 'hourly'">
                                        <small>{{ t('bookingFlow.perHour') }}</small>
                                        <strong>{{ formatMoney(vehicle.hourly_rate) }}</strong>
                                    </template>
                                    <span>{{ t('bookingFlow.estimated') }}</span>
                                    <b>{{ formatMoney(vehicle.estimated_total) }}</b>
                                    <em v-if="!vehicle.available">{{ t('bookingFlow.noCapacity') }}</em>
                                </div>
                            </button>
                        </article>
                    </div>

                    <div class="wizard-extras">
                        <div class="wizard-panel__heading wizard-panel__heading--split">
                            <span><AppIcon name="bag" :size="18" /></span>
                            <div><h3>{{ t('bookingFlow.extras') }}</h3><p>{{ t('bookingFlow.extrasText') }}</p></div>
                        </div>
                        <div class="extra-list">
                            <article v-for="extra in extras" :key="extra.id" class="extra-option" :class="{ selected: extraQuantities[extra.key] > 0 }">
                                <div>
                                    <h4>{{ extra.name }}</h4>
                                    <p>{{ extra.description }}</p>
                                    <span>{{ formatMoney(extra.price) }} {{ extra.pricing_type === 'per_booking' ? t('bookingFlow.once') : t('bookingFlow.each') }}</span>
                                </div>
                                <div v-if="extra.pricing_type === 'per_item'" class="quantity-control" :aria-label="extra.name">
                                    <button type="button" :disabled="!extraQuantities[extra.key]" @click="setExtra(extra, extraQuantities[extra.key] - 1)">−</button>
                                    <output>{{ extraQuantities[extra.key] || 0 }}</output>
                                    <button type="button" :disabled="extraQuantities[extra.key] >= extra.max_quantity" @click="setExtra(extra, extraQuantities[extra.key] + 1)">+</button>
                                </div>
                                <button v-else type="button" class="extra-toggle" :class="{ active: extraQuantities[extra.key] }" :aria-pressed="Boolean(extraQuantities[extra.key])" @click="toggleFixedExtra(extra)">
                                    <AppIcon v-if="extraQuantities[extra.key]" name="check" :size="15" />
                                    {{ extraQuantities[extra.key] ? t('bookingFlow.selected') : t('bookingFlow.add') }}
                                </button>
                            </article>
                        </div>
                    </div>

                    <div class="wizard-total-strip">
                        <div aria-live="polite"><span>{{ t('bookingFlow.total') }}</span><strong>{{ formatMoney(estimatedTotal) }}</strong></div>
                        <button class="button button--gold button--large" :disabled="!selectedVehicle" @click="continueToDetails">
                            {{ t('bookingFlow.continueDetails') }} <AppIcon name="arrow" :size="18" />
                        </button>
                    </div>
                </div>

                <div v-else-if="step === 4" key="details" class="wizard-panel">
                    <div class="wizard-panel__heading">
                        <span>4</span>
                        <div><h3>{{ t('bookingFlow.yourDetails') }}</h3><p>{{ t('bookingFlow.yourDetailsText') }}</p></div>
                    </div>

                    <div class="wizard-detail-grid">
                        <label class="field"><span>{{ t('bookingFlow.firstName') }} *</span><input v-model="customer.first_name" autocomplete="given-name" required></label>
                        <label class="field"><span>{{ t('bookingFlow.lastName') }} *</span><input v-model="customer.last_name" autocomplete="family-name" required></label>
                        <label class="field"><span>{{ t('bookingFlow.email') }} *</span><input v-model="customer.email" type="email" autocomplete="email" required></label>
                        <label class="field"><span>{{ t('bookingFlow.phone') }} *</span><input v-model="customer.phone" type="tel" autocomplete="tel" required></label>
                        <label class="field field--wide"><span>{{ t('bookingFlow.invoiceAddress') }} <small>{{ t('bookingFlow.optional') }}</small></span><input v-model="customer.invoice_address" autocomplete="street-address" :placeholder="t('bookingFlow.invoicePlaceholder')"></label>
                        <label class="field field--wide"><span>{{ t('bookingFlow.comments') }}</span><textarea v-model="customer.message" rows="3" :placeholder="t('bookingFlow.commentsPlaceholder')"></textarea></label>
                    </div>

                    <fieldset class="payment-options">
                        <legend>{{ t('bookingFlow.payment') }}</legend>
                        <label :class="{ active: customer.payment_method === 'cash' }">
                            <input v-model="customer.payment_method" type="radio" value="cash">
                            <span class="payment-options__icon">€</span>
                            <span><strong>{{ t('bookingFlow.cash') }}</strong><small>{{ t('bookingFlow.cashText') }}</small></span>
                        </label>
                        <label :class="{ active: customer.payment_method === 'card' }">
                            <input v-model="customer.payment_method" type="radio" value="card">
                            <span class="payment-options__icon">▰</span>
                            <span><strong>{{ t('bookingFlow.card') }}</strong><small>{{ t('bookingFlow.cardText') }}</small></span>
                        </label>
                    </fieldset>

                    <label class="checkbox-field wizard-consent">
                        <input v-model="customer.consent" type="checkbox" required>
                        <span>{{ t('bookingFlow.consent') }}</span>
                    </label>

                    <div class="wizard-actions">
                        <button type="button" class="button button--ghost wizard-back" @click="step = 3">{{ t('bookingFlow.back') }}</button>
                        <button type="button" class="button button--gold button--large" @click="continueToReview">{{ t('bookingFlow.continueReview') }} <AppIcon name="arrow" :size="18" /></button>
                    </div>
                </div>

                <div v-else key="review" class="wizard-panel wizard-review">
                    <div class="wizard-panel__heading">
                        <span>5</span>
                        <div><h3>{{ t('bookingFlow.review') }}</h3><p>{{ t('bookingFlow.reviewText') }}</p></div>
                    </div>

                    <div class="review-grid">
                        <section class="review-card">
                            <div class="review-card__head"><h4>{{ t('bookingFlow.journey') }}</h4><button type="button" @click="step = 1">{{ t('bookingFlow.edit') }}</button></div>
                            <dl>
                                <div><dt>{{ t('bookingFlow.pickup') }}</dt><dd>{{ journey.pickup }}</dd></div>
                                <div v-if="journey.dropoff"><dt>{{ t('bookingFlow.dropoff') }}</dt><dd>{{ journey.dropoff }}</dd></div>
                                <div><dt>{{ t('bookingFlow.date') }}</dt><dd>{{ formatDate(journey.pickup_date) }} · {{ journey.pickup_time }}</dd></div>
                                <div><dt>{{ t('bookingFlow.tripType') }}</dt><dd>{{ journey.booking_mode === 'hourly' ? `${journey.hourly_duration_hours} ${t('bookingFlow.hoursPlural')}` : (journey.trip_type === 'return' ? t('bookingFlow.return') : t('bookingFlow.oneWay')) }}</dd></div>
                                <div><dt>{{ t('bookingFlow.passengers') }}</dt><dd>{{ journey.passengers }} · {{ journey.luggage }} {{ t('bookingFlow.bags') }}</dd></div>
                            </dl>
                        </section>
                        <section class="review-card">
                                <div class="review-card__head"><h4>{{ t('bookingFlow.customer') }}</h4><button type="button" @click="step = 4">{{ t('bookingFlow.edit') }}</button></div>
                            <dl>
                                <div><dt>{{ t('bookingFlow.firstName') }}</dt><dd>{{ customer.first_name }} {{ customer.last_name }}</dd></div>
                                <div><dt>{{ t('bookingFlow.email') }}</dt><dd>{{ customer.email }}</dd></div>
                                <div><dt>{{ t('bookingFlow.phone') }}</dt><dd>{{ customer.phone }}</dd></div>
                                <div><dt>{{ t('bookingFlow.payment') }}</dt><dd>{{ customer.payment_method === 'cash' ? t('bookingFlow.cash') : t('bookingFlow.card') }}</dd></div>
                            </dl>
                        </section>
                        <section class="review-card">
                            <div class="review-card__head"><h4>{{ t('bookingFlow.vehicle') }}</h4><button type="button" @click="step = 3">{{ t('bookingFlow.edit') }}</button></div>
                            <div class="review-vehicle">
                                <img v-if="selectedVehicle.image_url" :src="selectedVehicle.image_url" :alt="selectedVehicle.name">
                                <div><strong>{{ selectedVehicle.name }}</strong><span>{{ selectedVehicle.max_passengers }} {{ t('bookingFlow.people') }} · {{ selectedVehicle.max_luggage }} {{ t('bookingFlow.bags') }}</span></div>
                            </div>
                        </section>
                        <section class="review-card">
                            <div class="review-card__head"><h4>{{ t('bookingFlow.extrasSummary') }}</h4><button type="button" @click="step = 3">{{ t('bookingFlow.edit') }}</button></div>
                            <ul class="review-extras">
                                <li v-for="extra in selectedExtras" :key="extra.id"><span>{{ extra.name }} <template v-if="extra.pricing_type === 'per_item'">× {{ extra.quantity }}</template></span><strong>{{ formatMoney(extra.pricing_type === 'per_booking' ? extra.price : extra.price * extra.quantity) }}</strong></li>
                                <li v-if="!selectedExtras.length"><span>{{ t('bookingFlow.none') }}</span></li>
                            </ul>
                        </section>
                    </div>

                    <section class="price-summary">
                        <span class="eyebrow">{{ t('bookingFlow.priceBreakdown') }}</span>
                        <div class="price-summary__line">
                            <span>{{ t('bookingFlow.vehicleFare') }} <small>{{ journey.booking_mode === 'hourly' ? `${journey.hourly_duration_hours} × ${formatMoney(selectedVehicle.hourly_rate)}` : `${formatDistance(billedDistance)} ${t('bookingFlow.km')} × ${formatMoney(selectedVehicle.rate_per_km)}` }}</small></span>
                            <strong>{{ formatMoney(selectedVehicle.estimated_total) }}</strong>
                        </div>
                        <div v-if="extrasTotal" class="price-summary__line"><span>{{ t('bookingFlow.extrasFare') }}</span><strong>{{ formatMoney(extrasTotal) }}</strong></div>
                        <div class="price-summary__total"><span>{{ t('bookingFlow.total') }}</span><strong>{{ formatMoney(estimatedTotal) }}</strong></div>
                    </section>

                    <p class="wizard-request-note"><AppIcon name="shield" :size="18" /> {{ t('bookingFlow.requestNote') }}</p>
                    <div class="wizard-actions">
                        <button type="button" class="button button--ghost wizard-back" @click="step = 4">{{ t('bookingFlow.back') }}</button>
                        <button type="button" class="button button--gold button--large" :disabled="submitting" @click="submitBooking">
                            {{ submitting ? t('bookingFlow.sending') : t('bookingFlow.send') }} <AppIcon v-if="!submitting" name="arrow" :size="18" />
                        </button>
                    </div>
                </div>
            </Transition>
        </template>

        <div v-else class="booking-success" role="status">
            <span class="booking-success__icon"><AppIcon name="check" :size="30" /></span>
            <span class="eyebrow">{{ t('bookingFlow.reference') }}</span>
            <strong>{{ success.reference }}</strong>
            <h3>{{ t('bookingFlow.successTitle') }}</h3>
            <p>{{ t('bookingFlow.successText') }}</p>
            <div><span>{{ t('bookingFlow.total') }}</span><b>{{ formatMoney(success.estimated_total) }}</b></div>
            <button type="button" class="button button--gold" @click="startAgain">{{ t('bookingFlow.newRequest') }}</button>
        </div>
    </section>
</template>
