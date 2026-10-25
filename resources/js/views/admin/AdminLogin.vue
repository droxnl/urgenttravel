<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import SiteLogo from '../../components/SiteLogo.vue';
import { api, validationMessage } from '../../lib/api';

const router = useRouter();
const busy = ref(false);
const error = ref('');
const form = reactive({ email: '', password: '', remember: true });
const visualStyle = {
    backgroundImage: "linear-gradient(90deg, rgba(10, 10, 12, 0.85), rgba(10, 10, 12, 0.65)), url('/images/uploads/WhatsApp-Image-2026-01-14-at-21.05.57.jpeg')",
};

async function submit() {
    busy.value = true;
    error.value = '';
    try {
        await api.adminLogin(form);
        router.push('/admin');
    } catch (exception) {
        error.value = validationMessage(exception);
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <div class="admin-login">
        <div class="admin-login__visual" :style="visualStyle">
            <SiteLogo light />
            <div>
                <span class="eyebrow eyebrow--gold">Urgent Travel Services</span>
                <h1>Content manager</h1>
                <p>Edit every page, keep the SEO current and shape the next journey with AI-assisted writing.</p>
            </div>
            <span class="admin-login__foot">Private transport, considered.</span>
        </div>
        <div class="admin-login__panel">
            <form class="admin-login__form" @submit.prevent="submit">
                <span class="eyebrow">Secure access</span>
                <h2>Welcome back.</h2>
                <p>Sign in to manage the website.</p>
                <label class="field">
                    <span>Email address</span>
                    <input v-model="form.email" type="email" required autocomplete="username" placeholder="admin@urgent-travelservices.nl">
                </label>
                <label class="field">
                    <span>Password</span>
                    <input v-model="form.password" type="password" required autocomplete="current-password" placeholder="Your password">
                </label>
                <label class="checkbox-field">
                    <input v-model="form.remember" type="checkbox">
                    <span>Keep me signed in on this device</span>
                </label>
                <p v-if="error" class="form-alert form-alert--error" role="alert">{{ error }}</p>
                <button class="button button--gold button--large" type="submit" :disabled="busy">
                    {{ busy ? 'Signing in…' : 'Sign in to content manager' }}
                </button>
                <RouterLink to="/" class="text-link">← Back to website</RouterLink>
            </form>
        </div>
    </div>
</template>
