<script setup>
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import AppIcon from '../../components/AppIcon.vue';
import { api, validationMessage } from '../../lib/api';

const loading = ref(true);
const saving = ref(false);
const testing = ref(false);
const mailTesting = ref(false);
const mailTokenChecking = ref(false);
const mailSendingTest = ref(false);
const error = ref('');
const notice = ref('');
const configured = ref(false);
const mailConfigured = ref(false);
const mailTokenSaved = ref(false);
const mailboxes = ref([]);
const testRecipient = ref('');
const active = ref('website');
const form = reactive({});
let mailboxLookupTimer;
let mailboxLookupRequest = 0;

onMounted(async () => {
    try {
        const response = await api.adminSettings();
        Object.assign(form, response.settings);
        configured.value = response.openai_configured;
        mailConfigured.value = response.mail_configured;
        mailTokenSaved.value = response.mail_credentials_saved;
        form.openai_enabled = String(form.openai_enabled) === '1' || form.openai_enabled === true;
        form.openai_auto_seo = String(form.openai_auto_seo) === '1' || form.openai_auto_seo === true;
        form.mail_enabled = String(form.mail_enabled) === '1' || form.mail_enabled === true;
        form.mail_send_admin = String(form.mail_send_admin) === '1' || form.mail_send_admin === true;
        form.mail_send_customer = String(form.mail_send_customer) === '1' || form.mail_send_customer === true;
        form.openai_api_key = '';
        form.hostinger_mail_api_token = '';
        testRecipient.value = form.mail_notification_email || form.email || '';
    } catch (exception) {
        error.value = validationMessage(exception);
    } finally {
        loading.value = false;
    }
});

async function save() {
    saving.value = true;
    error.value = '';
    notice.value = '';
    try {
        const response = await api.adminUpdateSettings(form);
        Object.assign(form, response.settings);
        configured.value = Boolean(form.openai_enabled && (form.openai_api_key || configured.value));
        mailConfigured.value = Boolean(response.mail_configured);
        mailTokenSaved.value = Boolean(response.mail_credentials_saved);
        form.openai_api_key = '';
        form.hostinger_mail_api_token = '';
        notice.value = 'Settings saved.';
    } catch (exception) {
        error.value = validationMessage(exception);
    } finally {
        saving.value = false;
    }
}

async function testAi() {
    testing.value = true;
    error.value = '';
    notice.value = '';
    try {
        const result = await api.adminTestOpenAi();
        configured.value = true;
        notice.value = `Connection successful. ${result.models_available} models are visible to this key.`;
    } catch (exception) {
        error.value = validationMessage(exception);
    } finally {
        testing.value = false;
    }
}

async function testMail() {
    clearTimeout(mailboxLookupTimer);
    mailboxLookupRequest += 1;
    mailTokenChecking.value = false;
    mailTesting.value = true;
    error.value = '';
    notice.value = '';
    try {
        const result = await api.adminTestMail({
            hostinger_mail_api_token: form.hostinger_mail_api_token || null,
        });
        applyMailboxes(result.mailboxes || [], false);
    } catch (exception) {
        error.value = validationMessage(exception);
    } finally {
        mailTesting.value = false;
    }
}

function selectMailbox(mailbox) {
    form.hostinger_mailbox_resource_id = mailbox.resource_id;
    form.mail_sender_email = mailbox.address;
}

function applyMailboxes(available, automatic) {
    mailboxes.value = available;
    if (!available.length) {
        error.value = 'Hostinger connected, but no sender mailboxes were returned.';
        return;
    }

    const currentSender = String(form.mail_sender_email || '').toLowerCase();
    const selected = available.find((mailbox) => mailbox.address?.toLowerCase() === currentSender) || available[0];
    selectMailbox(selected);
    notice.value = automatic
        ? `Mailbox ${selected.address} was detected automatically. Save the email settings to finish.`
        : `Hostinger account connected. ${available.length} mailbox${available.length === 1 ? '' : 'es'} available.`;
}

function onMailTokenInput(event) {
    const token = String(event.target.value || '').trim();
    clearTimeout(mailboxLookupTimer);
    mailboxLookupRequest += 1;
    mailTokenChecking.value = false;
    form.hostinger_mailbox_resource_id = '';
    mailboxes.value = [];
    error.value = '';
    notice.value = '';

    if (token.length < 8) return;
    mailboxLookupTimer = window.setTimeout(() => discoverMailbox(token), 700);
}

async function discoverMailbox(token) {
    const request = ++mailboxLookupRequest;
    mailTokenChecking.value = true;
    try {
        const result = await api.adminTestMail({ hostinger_mail_api_token: token });
        if (request !== mailboxLookupRequest) return;
        applyMailboxes(result.mailboxes || [], true);
    } catch (exception) {
        if (request !== mailboxLookupRequest) return;
        error.value = validationMessage(exception);
    } finally {
        if (request === mailboxLookupRequest) mailTokenChecking.value = false;
    }
}

async function sendTestMail() {
    mailSendingTest.value = true;
    error.value = '';
    notice.value = '';
    try {
        const result = await api.adminSendTestMail({ recipient: testRecipient.value });
        notice.value = result.message;
    } catch (exception) {
        error.value = validationMessage(exception);
    } finally {
        mailSendingTest.value = false;
    }
}

onBeforeUnmount(() => {
    clearTimeout(mailboxLookupTimer);
});
</script>

<template>
    <div class="admin-page-head">
        <div><span class="eyebrow">Configuration</span><h1>Settings, email & AI</h1><p>Business details, Hostinger mail delivery and the writing rules behind your content.</p></div>
    </div>
    <p v-if="error" class="form-alert form-alert--error">{{ error }}</p>
    <p v-if="notice" class="form-alert form-alert--success">{{ notice }}</p>
    <div v-if="loading" class="admin-empty">Loading settings…</div>
    <template v-else>
        <div class="settings-tabs">
            <button :class="{ active: active === 'website' }" @click="active = 'website'">Website & contact</button>
            <button :class="{ active: active === 'email' }" @click="active = 'email'"><AppIcon name="mail" :size="16" /> Email notifications</button>
            <button :class="{ active: active === 'openai' }" @click="active = 'openai'"><AppIcon name="sparkles" :size="16" /> OpenAI</button>
        </div>
        <section v-if="active === 'website'" class="admin-panel editor-panel">
            <div class="editor-panel__head"><div><h2>Business details</h2><p>Used by the header, footer, contact page and structured communication.</p></div></div>
            <div class="form-grid form-grid--admin">
                <label class="field"><span>Business name</span><input v-model="form.site_name"></label>
                <label class="field"><span>Tagline</span><input v-model="form.site_tagline"></label>
                <label class="field"><span>Phone</span><input v-model="form.phone"></label>
                <label class="field"><span>WhatsApp number</span><input v-model="form.whatsapp"></label>
                <label class="field field--wide"><span>Email</span><input v-model="form.email" type="email"></label>
                <label class="field field--wide"><span>Address</span><input v-model="form.address"></label>
                <label class="field"><span>City</span><input v-model="form.city"></label>
                <label class="field"><span>Postcode</span><input v-model="form.postcode"></label>
                <label class="field"><span>Country</span><input v-model="form.country"></label>
                <label class="field"><span>Booking URL</span><input v-model="form.booking_url"></label>
                <label class="field"><span>Chamber of Commerce</span><input v-model="form.kvk"></label>
                <label class="field"><span>VAT number</span><input v-model="form.vat"></label>
            </div>
            <button class="button button--gold" :disabled="saving" @click="save">{{ saving ? 'Saving…' : 'Save business details' }}</button>
        </section>

        <section v-if="active === 'email'" class="admin-panel editor-panel">
            <div class="editor-panel__head">
                <div><h2>Hostinger Mail API</h2><p>Send booking alerts to your team and immediate confirmations to customers.</p></div>
                <span class="connection-pill" :class="{ connected: mailConfigured }"><i></i>{{ mailConfigured ? 'Configured' : 'Not configured' }}</span>
            </div>
            <div class="ai-config-note">
                <AppIcon name="mail" :size="23" />
                <p>In Hostinger hPanel, open <strong>Emails → Agentic Mail → API access</strong>, create a token for the sender mailbox and paste it below. We connect automatically, load the available mailboxes and select the matching resource ID and sender address. The token is encrypted and never returned to the browser.</p>
            </div>
            <div class="form-grid form-grid--admin">
                <label class="checkbox-field field--wide"><input v-model="form.mail_enabled" type="checkbox"><span>Enable booking and contact email notifications</span></label>
                <label class="field field--wide">
                    <span>Hostinger Mail API token <small>{{ mailConfigured ? 'Leave blank to keep the saved token' : 'Required' }}</small></span>
                    <input v-model="form.hostinger_mail_api_token" type="password" autocomplete="new-password" placeholder="Paste the Agentic Mail API token" @input="onMailTokenInput">
                </label>
                <div v-if="mailTokenChecking" class="mail-auto-status field--wide" role="status">
                    <span class="address-autocomplete__spinner"></span>
                    <span>Connecting to Hostinger and detecting your sender mailbox…</span>
                </div>
                <label class="field">
                    <span>Mailbox resource ID <small>Filled automatically</small></span>
                    <input v-model="form.hostinger_mailbox_resource_id" placeholder="AC…">
                </label>
                <label class="field">
                    <span>Sender email</span>
                    <input v-model="form.mail_sender_email" type="email" placeholder="info@urgent-travelservices.nl">
                </label>
                <label class="field"><span>Sender display name</span><input v-model="form.mail_sender_name"></label>
                <label class="field"><span>Team notification email</span><input v-model="form.mail_notification_email" type="email"></label>
                <label class="checkbox-field"><input v-model="form.mail_send_admin" type="checkbox"><span>Notify our team about each new request</span></label>
                <label class="checkbox-field"><input v-model="form.mail_send_customer" type="checkbox"><span>Send a request-received confirmation to the customer</span></label>
            </div>
            <div class="ai-config-actions">
                <button class="button button--gold" :disabled="saving" @click="save">{{ saving ? 'Saving…' : 'Save email settings' }}</button>
                <button class="button button--ghost" :disabled="mailTesting || mailTokenChecking || (!form.hostinger_mail_api_token && !mailTokenSaved)" @click="testMail">{{ mailTesting ? 'Connecting…' : 'Refresh mailboxes' }}</button>
            </div>
            <div v-if="mailboxes.length" class="mailbox-picker">
                <span class="eyebrow">Available sender mailboxes</span>
                <div class="mailbox-picker__grid">
                    <button v-for="mailbox in mailboxes" :key="mailbox.resource_id" type="button" :class="{ selected: form.hostinger_mailbox_resource_id === mailbox.resource_id }" @click="selectMailbox(mailbox)">
                        <AppIcon name="mail" :size="18" />
                        <span><strong>{{ mailbox.address }}</strong><small>{{ mailbox.resource_id }}</small></span>
                        <AppIcon v-if="form.hostinger_mailbox_resource_id === mailbox.resource_id" name="check" :size="16" />
                    </button>
                </div>
            </div>
            <div class="mail-test-box">
                <div><span class="eyebrow">Send a live test</span><p>Save the settings and select a sender mailbox first. The message is sent through the Hostinger API.</p></div>
                <div class="mail-test-box__actions">
                    <input v-model="testRecipient" type="email" aria-label="Test email recipient" placeholder="you@yourdomain.com">
                    <button class="button button--ghost button--small" :disabled="mailSendingTest || !mailConfigured || !testRecipient" @click="sendTestMail">{{ mailSendingTest ? 'Sending…' : 'Send test email' }}</button>
                </div>
            </div>
            <p class="form-hint">Customer confirmations make it clear that a request is not yet a confirmed booking. Every send attempt is logged against the inquiry.</p>
        </section>

        <section v-if="active === 'openai'" class="admin-panel editor-panel">
            <div class="editor-panel__head">
                <div><h2>OpenAI copy assistant</h2><p>Configure the model once, then generate titles, SEO and editorial drafts from any page.</p></div>
                <span class="connection-pill" :class="{ connected: configured }"><i></i>{{ configured ? 'Configured' : 'Not configured' }}</span>
            </div>
            <div class="ai-config-note">
                <AppIcon name="shield" :size="23" />
                <p>Your API key is encrypted in the database. Once saved it is never returned to the browser. You can instead set <code>OPENAI_API_KEY</code> in the server environment.</p>
            </div>
            <div class="form-grid form-grid--admin">
                <label class="checkbox-field field--wide"><input v-model="form.openai_enabled" type="checkbox"><span>Enable OpenAI in the content manager</span></label>
                <label class="field field--wide"><span>API key <small>{{ configured ? 'Leave blank to keep the saved key' : 'Required to enable AI' }}</small></span><input v-model="form.openai_api_key" type="password" autocomplete="new-password" placeholder="sk-proj-…"></label>
                <label class="field"><span>Model</span><input v-model="form.openai_model" placeholder="gpt-4o-mini"></label>
                <label class="field"><span>Base URL</span><input v-model="form.openai_base_url" placeholder="https://api.openai.com"></label>
                <label class="field"><span>Content language</span>
                    <select v-model="form.openai_language"><option value="en">English</option><option value="nl">Nederlands</option></select>
                </label>
                <label class="field"><span>Tone</span><input v-model="form.openai_tone"></label>
                <label class="checkbox-field field--wide"><input v-model="form.openai_auto_seo" type="checkbox"><span>Allow AI SEO generation when enabled on a page save</span></label>
                <label class="field field--wide"><span>System instructions</span><textarea v-model="form.openai_system_prompt" rows="7"></textarea></label>
            </div>
            <div class="ai-config-actions">
                <button class="button button--gold" :disabled="saving" @click="save">{{ saving ? 'Saving…' : 'Save AI settings' }}</button>
                <button class="button button--ghost" :disabled="testing || !configured" @click="testAi">{{ testing ? 'Testing…' : 'Test connection' }}</button>
            </div>
            <p class="form-hint">Save a new key before testing the connection.</p>
        </section>
    </template>
</template>
