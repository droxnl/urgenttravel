import axios from 'axios';

const client = axios.create({
    baseURL: '/api',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

export const initialBootstrap = window.__APP_BOOTSTRAP__ || {};

export const api = {
    getPage(path) {
        return client.get('/page', { params: { path } }).then(({ data }) => data.data);
    },
    getSite() {
        return client.get('/site').then(({ data }) => data);
    },
    getBookingConfig(locale) {
        return client.get('/booking/config', { params: { locale } }).then(({ data }) => data);
    },
    searchBookingAddresses(query, locale) {
        return client.get('/booking/addresses', { params: { q: query, locale } }).then(({ data }) => data);
    },
    getBookingQuote(payload) {
        return client.post('/booking/quote', payload).then(({ data }) => data);
    },
    createInquiry(payload) {
        return client.post('/inquiries', payload).then(({ data }) => data);
    },
    adminLogin(payload) {
        return client.post('/admin/login', payload).then(({ data }) => data);
    },
    adminLogout() {
        return client.post('/admin/logout').then(({ data }) => data);
    },
    adminMe() {
        return client.get('/admin/me').then(({ data }) => data);
    },
    adminPages() {
        return client.get('/admin/pages').then(({ data }) => data.data);
    },
    adminPage(id) {
        return client.get(`/admin/pages/${id}`).then(({ data }) => data.data);
    },
    adminUpdatePage(id, payload) {
        return client.put(`/admin/pages/${id}`, payload).then(({ data }) => data.data);
    },
    adminUpdatePageTranslation(id, locale, payload) {
        return client.put(`/admin/pages/${id}/translations/${locale}`, payload).then(({ data }) => data.data);
    },
    adminUpdatePageBlocks(id, locale, payload) {
        return client.put(`/admin/pages/${id}/blocks/${locale}`, payload).then(({ data }) => data.data);
    },
    adminGenerate(id, payload) {
        return client.post(`/admin/pages/${id}/ai`, payload).then(({ data }) => data.data);
    },
    adminSettings() {
        return client.get('/admin/settings').then(({ data }) => data);
    },
    adminUpdateSettings(payload) {
        return client.put('/admin/settings', payload).then(({ data }) => data);
    },
    adminTestOpenAi() {
        return client.post('/admin/settings/test-openai').then(({ data }) => data);
    },
    adminTestMail(payload = {}) {
        return client.post('/admin/settings/test-mail', payload).then(({ data }) => data);
    },
    adminSendTestMail(payload) {
        return client.post('/admin/settings/send-test-mail', payload).then(({ data }) => data);
    },
    adminBookingSettings() {
        return client.get('/admin/booking-settings').then(({ data }) => data);
    },
    adminUpdateBookingSettings(payload) {
        return client.put('/admin/booking-settings', payload).then(({ data }) => data);
    },
    adminInquiries() {
        return client.get('/admin/inquiries').then(({ data }) => data);
    },
    adminUpdateInquiry(id, payload) {
        return client.patch(`/admin/inquiries/${id}`, payload).then(({ data }) => data);
    },
    adminSendInquiryNotifications(id) {
        return client.post(`/admin/inquiries/${id}/notify`).then(({ data }) => data);
    },
    adminDeleteInquiry(id) {
        return client.delete(`/admin/inquiries/${id}`).then(({ data }) => data);
    },
    adminStats() {
        return client.get('/admin/stats').then(({ data }) => data);
    },
    adminMedia() {
        return client.get('/admin/media').then(({ data }) => data.media);
    },
    adminUploadMedia(formData) {
        return client.post('/admin/media', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        }).then(({ data }) => data);
    },
};

export function validationMessage(error) {
    const errors = error?.response?.data?.errors;
    if (errors) {
        return Object.values(errors).flat().join(' ');
    }
    return error?.response?.data?.message || error?.message || 'Something went wrong.';
}
