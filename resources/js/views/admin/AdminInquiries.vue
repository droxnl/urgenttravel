<script setup>
import { onMounted, ref } from 'vue';
import AppIcon from '../../components/AppIcon.vue';
import { api, validationMessage } from '../../lib/api';

const inquiries = ref([]);
const loading = ref(true);
const error = ref('');
const notice = ref('');
const resending = ref(null);
const filter = ref('all');
const selected = ref(null);

const statuses = ['new', 'contacted', 'confirmed', 'completed', 'cancelled'];

function formatDate(value) {
    if (!value) return '';
    return new Date(value).toLocaleDateString('en-GB');
}

async function load() {
    loading.value = true;
    try {
        const response = await api.adminInquiries();
        inquiries.value = response.data;
    } catch (exception) {
        error.value = validationMessage(exception);
    } finally {
        loading.value = false;
    }
}

onMounted(load);

async function updateStatus(inquiry, status) {
    try {
        await api.adminUpdateInquiry(inquiry.id, { status });
        inquiry.status = status;
    } catch (exception) {
        error.value = validationMessage(exception);
    }
}

async function resend(inquiry) {
    resending.value = inquiry.id;
    error.value = '';
    notice.value = '';
    try {
        const response = await api.adminSendInquiryNotifications(inquiry.id);
        Object.assign(inquiry, response.inquiry);
        notice.value = response.message;
    } catch (exception) {
        error.value = validationMessage(exception);
    } finally {
        resending.value = null;
    }
}

async function remove(inquiry) {
    if (!window.confirm(`Delete inquiry ${inquiry.reference}? This cannot be undone.`)) return;
    try {
        await api.adminDeleteInquiry(inquiry.id);
        inquiries.value = inquiries.value.filter((item) => item.id !== inquiry.id);
        if (selected.value?.id === inquiry.id) selected.value = null;
    } catch (exception) {
        error.value = validationMessage(exception);
    }
}
</script>

<template>
    <div class="admin-page-head admin-page-head--row">
        <div><span class="eyebrow">Requests</span><h1>Inquiries</h1><p>Booking and contact enquiries submitted through the website.</p></div>
        <select v-model="filter" class="admin-filter"><option value="all">All statuses</option><option v-for="status in statuses" :key="status" :value="status">{{ status }}</option></select>
    </div>
    <p v-if="error" class="form-alert form-alert--error">{{ error }}</p>
    <p v-if="notice" class="form-alert form-alert--success">{{ notice }}</p>
    <div class="inquiry-layout">
        <div class="admin-panel admin-panel--flush">
            <div v-if="loading" class="admin-empty">Loading inquiries…</div>
            <div v-else-if="!inquiries.length" class="admin-empty">No website inquiries yet.</div>
            <div v-else class="inquiry-list">
                <button
                    v-for="inquiry in inquiries.filter((item) => filter === 'all' || item.status === filter)"
                    :key="inquiry.id"
                    type="button"
                    :class="{ active: selected?.id === inquiry.id }"
                    @click="selected = inquiry"
                >
                    <span class="status-dot" :class="`status-dot--${inquiry.status}`"></span>
                    <div><strong>{{ inquiry.name }}</strong><span>{{ inquiry.type === 'contact' ? 'Contact enquiry' : `${inquiry.pickup || 'Journey'} → ${inquiry.dropoff || 'Destination'}` }}</span></div>
                    <span class="inquiry-list__meta">{{ inquiry.pickup_date ? formatDate(inquiry.pickup_date) : formatDate(inquiry.created_at) }}</span>
                    <AppIcon name="arrow" :size="16" />
                </button>
            </div>
        </div>
        <aside class="inquiry-detail admin-panel">
            <template v-if="selected">
                <div class="inquiry-detail__head">
                    <div><span class="eyebrow">{{ selected.reference }}</span><h2>{{ selected.name }}</h2></div>
                    <span class="template-pill">{{ selected.type }}</span>
                </div>
                <div class="inquiry-detail__contact">
                    <a :href="`mailto:${selected.email}`">{{ selected.email }}</a>
                    <a :href="`tel:${selected.phone}`">{{ selected.phone }}</a>
                </div>
                <dl>
                    <div><dt>Status</dt><dd><select :value="selected.status" @change="updateStatus(selected, $event.target.value)"><option v-for="status in statuses" :key="status">{{ status }}</option></select></dd></div>
                    <div v-if="selected.pickup"><dt>Pickup</dt><dd>{{ selected.pickup }}</dd></div>
                    <div v-if="selected.dropoff"><dt>Drop-off</dt><dd>{{ selected.dropoff }}</dd></div>
                    <div v-if="selected.pickup_date"><dt>Date & time</dt><dd>{{ formatDate(selected.pickup_date) }} · {{ selected.pickup_time }}</dd></div>
                    <div v-if="selected.booking_mode"><dt>Journey type</dt><dd>{{ selected.booking_mode === 'hourly' ? `Hourly · ${selected.hourly_duration_hours} hours` : selected.trip_type === 'return' ? 'Return transfer' : 'One-way transfer' }}</dd></div>
                    <div v-if="selected.vehicle_name"><dt>Vehicle</dt><dd>{{ selected.vehicle_name }}</dd></div>
                    <div v-if="selected.route_distance_km !== null"><dt>Road distance</dt><dd>{{ Number(selected.route_distance_km).toLocaleString('en-GB') }} km<span v-if="selected.route_duration_minutes"> · {{ selected.route_duration_minutes }} min</span></dd></div>
                    <div v-if="selected.passengers"><dt>Passengers</dt><dd>{{ selected.passengers }}</dd></div>
                    <div v-if="selected.luggage !== null"><dt>Luggage</dt><dd>{{ selected.luggage }}</dd></div>
                    <div v-if="selected.flight_number"><dt>Flight</dt><dd>{{ selected.flight_number }}</dd></div>
                    <div v-if="selected.payment_method"><dt>Payment</dt><dd>{{ selected.payment_method === 'cash' ? 'Cash on pickup' : 'Card on pickup' }}</dd></div>
                    <div v-if="selected.invoice_address"><dt>Invoice address</dt><dd>{{ selected.invoice_address }}</dd></div>
                    <div v-if="selected.estimated_total !== null"><dt>Estimated total</dt><dd><strong>€ {{ Number(selected.estimated_total).toFixed(2) }}</strong></dd></div>
                    <div><dt>Received</dt><dd>{{ new Date(selected.created_at).toLocaleString('en-GB') }}</dd></div>
                    <div v-if="selected.message"><dt>Message</dt><dd>{{ selected.message }}</dd></div>
                </dl>
                <div v-if="selected.selected_extras?.length" class="mail-delivery-list">
                    <div class="mail-delivery-list__head"><span>Selected extras</span><span>{{ selected.selected_extras.length }} item{{ selected.selected_extras.length === 1 ? '' : 's' }}</span></div>
                    <div v-for="extra in selected.selected_extras" :key="extra.key" class="mail-delivery-row">
                        <span class="status-dot status-dot--confirmed"></span>
                        <div><strong>{{ extra.name_en }}{{ extra.pricing_type === 'per_item' ? ` × ${extra.quantity}` : '' }}</strong><span>€ {{ Number(extra.line_total).toFixed(2) }}</span></div>
                    </div>
                </div>
                <div v-if="selected.email_deliveries?.length" class="mail-delivery-list">
                    <div class="mail-delivery-list__head">
                        <span>Email notifications</span>
                        <span>{{ selected.email_deliveries.filter((delivery) => delivery.status === 'sent').length }}/{{ selected.email_deliveries.length }} sent</span>
                    </div>
                    <div v-for="delivery in selected.email_deliveries" :key="delivery.id" class="mail-delivery-row">
                        <span class="status-dot" :class="`status-dot--${delivery.status === 'sent' ? 'confirmed' : delivery.status === 'failed' ? 'cancelled' : 'new'}`"></span>
                        <div>
                            <strong>{{ delivery.type === 'admin_booking' ? 'Team notification' : 'Customer confirmation' }}</strong>
                            <span>{{ delivery.recipient }} · {{ delivery.status }}</span>
                            <small v-if="delivery.error">{{ delivery.error }}</small>
                        </div>
                    </div>
                </div>
                <div class="inquiry-detail__actions">
                    <a class="button button--gold" :href="`mailto:${selected.email}?subject=Your Urgent Travel Services request ${selected.reference}`">Reply by email</a>
                    <button class="button button--ghost button--small" :disabled="resending === selected.id" @click="resend(selected)">{{ resending === selected.id ? 'Sending…' : 'Resend notifications' }}</button>
                    <button class="icon-button icon-button--danger" title="Delete inquiry" @click="remove(selected)"><AppIcon name="trash" :size="18" /></button>
                </div>
            </template>
            <div v-else class="admin-empty">Select an inquiry to see the full request.</div>
        </aside>
    </div>
</template>
