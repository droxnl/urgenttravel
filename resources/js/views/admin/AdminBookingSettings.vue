<script setup>
import { onMounted, reactive, ref } from 'vue';
import AppIcon from '../../components/AppIcon.vue';
import { api, validationMessage } from '../../lib/api';

const loading = ref(true);
const saving = ref(false);
const uploadingId = ref(null);
const error = ref('');
const notice = ref('');
const active = ref('vehicles');
const data = reactive({ vehicles: [], extras: [] });

async function load() {
    loading.value = true;
    try {
        const result = await api.adminBookingSettings();
        data.vehicles = result.vehicles;
        data.extras = result.extras;
    } catch (exception) {
        error.value = validationMessage(exception);
    } finally {
        loading.value = false;
    }
}

onMounted(load);

function addVehicle() {
    data.vehicles.push({
        key: '',
        name_en: '',
        name_nl: '',
        badge_en: '',
        badge_nl: '',
        description_en: '',
        description_nl: '',
        category: 'comfort',
        rate_per_km: 0,
        hourly_rate: null,
        max_passengers: 4,
        max_luggage: 3,
        image: '',
        is_active: true,
        sort_order: (data.vehicles.length + 1) * 10,
    });
}

function addExtra() {
    data.extras.push({
        key: '',
        name_en: '',
        name_nl: '',
        description_en: '',
        description_nl: '',
        price: 0,
        pricing_type: 'per_item',
        max_quantity: 1,
        is_active: true,
        sort_order: (data.extras.length + 1) * 10,
    });
}

async function save() {
    saving.value = true;
    error.value = '';
    notice.value = '';
    try {
        const result = await api.adminUpdateBookingSettings({
            vehicles: data.vehicles,
            extras: data.extras,
        });
        data.vehicles = result.vehicles;
        data.extras = result.extras;
        notice.value = result.message;
    } catch (exception) {
        error.value = validationMessage(exception);
    } finally {
        saving.value = false;
    }
}

async function uploadImage(vehicle, event) {
    const file = event.target.files?.[0];
    if (!file) return;
    uploadingId.value = vehicle.id || vehicle.key;
    error.value = '';
    try {
        const body = new FormData();
        body.append('image', file);
        const uploaded = await api.adminUploadMedia(body);
        vehicle.image = uploaded.url;
    } catch (exception) {
        error.value = validationMessage(exception);
    } finally {
        uploadingId.value = null;
    }
}
</script>

<template>
    <div class="admin-page-head admin-page-head--row">
        <div>
            <span class="eyebrow">Booking configuration</span>
            <h1>Booking & tariffs</h1>
            <p>Manage every vehicle, capacity, fare and optional extra shown in the website booking flow.</p>
        </div>
        <button class="button button--gold" :disabled="saving || loading" @click="save">
            {{ saving ? 'Saving…' : 'Save all tariffs' }}
        </button>
    </div>

    <p v-if="error" class="form-alert form-alert--error" role="alert">{{ error }}</p>
    <p v-if="notice" class="form-alert form-alert--success" role="status">{{ notice }}</p>
    <div v-if="loading" class="admin-empty">Loading booking tariffs…</div>

    <template v-else>
        <div class="settings-tabs">
            <button :class="{ active: active === 'vehicles' }" @click="active = 'vehicles'">
                <AppIcon name="car" :size="16" /> Vehicles & fares
            </button>
            <button :class="{ active: active === 'extras' }" @click="active = 'extras'">
                <AppIcon name="bag" :size="16" /> Extras
            </button>
        </div>

        <section v-if="active === 'vehicles'" class="booking-admin-section">
            <div class="booking-admin-intro">
                <div><span class="eyebrow">Fare per kilometre</span><h2>Vehicles</h2><p>Distance prices use the road distance from the route calculator. Return journeys charge the distance twice. Leave an hourly rate empty to show “Price on request” for hourly hire.</p></div>
                <button class="button button--ghost" type="button" @click="addVehicle">Add vehicle</button>
            </div>

            <div class="booking-admin-list">
                <article v-for="(vehicle, index) in data.vehicles" :key="vehicle.id || `new-${index}`" class="booking-admin-card">
                    <header>
                        <div class="booking-admin-card__title">
                            <span class="booking-admin-card__number">{{ String(index + 1).padStart(2, '0') }}</span>
                            <div><strong>{{ vehicle.name_en || 'New vehicle' }}</strong><small>{{ vehicle.key || 'Add a unique key' }}</small></div>
                        </div>
                        <div class="booking-admin-card__controls">
                            <label><span>Order</span><input v-model.number="vehicle.sort_order" type="number" min="0"></label>
                            <label class="booking-admin-active"><input v-model="vehicle.is_active" type="checkbox"><span>{{ vehicle.is_active ? 'Visible' : 'Hidden' }}</span></label>
                        </div>
                    </header>
                    <div class="booking-admin-card__body">
                        <div class="booking-admin-image">
                            <img v-if="vehicle.image" :src="vehicle.image" :alt="vehicle.name_en">
                            <div v-else><AppIcon name="car" :size="30" /><span>No image</span></div>
                            <label class="upload-button">
                                <AppIcon name="upload" :size="15" />
                                {{ uploadingId === (vehicle.id || vehicle.key) ? 'Uploading…' : 'Upload image' }}
                                <input type="file" accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml" :disabled="uploadingId === (vehicle.id || vehicle.key)" @change="uploadImage(vehicle, $event)">
                            </label>
                        </div>
                        <div class="form-grid form-grid--admin booking-admin-fields">
                            <label class="field"><span>Unique key</span><input v-model="vehicle.key" placeholder="business-e-class"></label>
                            <label class="field"><span>Vehicle class</span>
                                <select v-model="vehicle.category">
                                    <option value="comfort">Comfort</option>
                                    <option value="business">Business</option>
                                    <option value="van">Van</option>
                                    <option value="luxury">First class</option>
                                </select>
                            </label>
                            <label class="field"><span>English name</span><input v-model="vehicle.name_en"></label>
                            <label class="field"><span>Nederlandse naam</span><input v-model="vehicle.name_nl"></label>
                            <label class="field"><span>English badge</span><input v-model="vehicle.badge_en"></label>
                            <label class="field"><span>Nederlandse badge</span><input v-model="vehicle.badge_nl"></label>
                            <label class="field field--wide"><span>English description</span><textarea v-model="vehicle.description_en" rows="3"></textarea></label>
                            <label class="field field--wide"><span>Nederlandse omschrijving</span><textarea v-model="vehicle.description_nl" rows="3"></textarea></label>
                            <label class="field"><span>Price per km (€)</span><input v-model.number="vehicle.rate_per_km" type="number" min="0" step="0.01"></label>
                            <label class="field"><span>Price per hour (€) <small>Optional</small></span><input v-model.number="vehicle.hourly_rate" type="number" min="0" step="0.01" placeholder="Price on request"></label>
                            <label class="field"><span>Maximum passengers</span><input v-model.number="vehicle.max_passengers" type="number" min="1" max="16"></label>
                            <label class="field"><span>Maximum luggage</span><input v-model.number="vehicle.max_luggage" type="number" min="1" max="24"></label>
                            <label class="field field--wide"><span>Image path or URL</span><input v-model="vehicle.image" placeholder="/images/uploads/vehicle.jpg"></label>
                        </div>
                    </div>
                </article>
            </div>
            <button class="button button--gold booking-admin-save" :disabled="saving" @click="save">{{ saving ? 'Saving…' : 'Save all tariffs' }}</button>
        </section>

        <section v-else class="booking-admin-section">
            <div class="booking-admin-intro">
                <div><span class="eyebrow">Optional services</span><h2>Extras</h2><p>Choose “per item” for seats or “once per booking” for services such as a personal gate pickup.</p></div>
                <button class="button button--ghost" type="button" @click="addExtra">Add extra</button>
            </div>

            <div class="booking-admin-list">
                <article v-for="(extra, index) in data.extras" :key="extra.id || `new-extra-${index}`" class="booking-admin-card">
                    <header>
                        <div class="booking-admin-card__title">
                            <span class="booking-admin-card__number">{{ String(index + 1).padStart(2, '0') }}</span>
                            <div><strong>{{ extra.name_en || 'New extra' }}</strong><small>{{ extra.key || 'Add a unique key' }}</small></div>
                        </div>
                        <div class="booking-admin-card__controls">
                            <label><span>Order</span><input v-model.number="extra.sort_order" type="number" min="0"></label>
                            <label class="booking-admin-active"><input v-model="extra.is_active" type="checkbox"><span>{{ extra.is_active ? 'Visible' : 'Hidden' }}</span></label>
                        </div>
                    </header>
                    <div class="booking-admin-card__body booking-admin-card__body--extra">
                        <div class="form-grid form-grid--admin booking-admin-fields">
                            <label class="field"><span>Unique key</span><input v-model="extra.key" placeholder="child-seat"></label>
                            <label class="field"><span>Pricing</span>
                                <select v-model="extra.pricing_type">
                                    <option value="per_item">Per item</option>
                                    <option value="per_booking">Once per booking</option>
                                </select>
                            </label>
                            <label class="field"><span>English name</span><input v-model="extra.name_en"></label>
                            <label class="field"><span>Nederlandse naam</span><input v-model="extra.name_nl"></label>
                            <label class="field"><span>Price (€)</span><input v-model.number="extra.price" type="number" min="0" step="0.01"></label>
                            <label class="field"><span>Maximum quantity</span><input v-model.number="extra.max_quantity" type="number" min="1" max="20" :disabled="extra.pricing_type === 'per_booking'"></label>
                            <label class="field field--wide"><span>English description</span><textarea v-model="extra.description_en" rows="2"></textarea></label>
                            <label class="field field--wide"><span>Nederlandse omschrijving</span><textarea v-model="extra.description_nl" rows="2"></textarea></label>
                        </div>
                    </div>
                </article>
            </div>
            <button class="button button--gold booking-admin-save" :disabled="saving" @click="save">{{ saving ? 'Saving…' : 'Save all extras' }}</button>
        </section>
    </template>
</template>
