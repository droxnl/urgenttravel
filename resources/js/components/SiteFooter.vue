<script setup>
import AppIcon from './AppIcon.vue';
import SiteLogo from './SiteLogo.vue';
import { siteState } from '../lib/seo';
import { getServices } from '../lib/content';
import { useI18n } from '../lib/i18n';
import { computed } from 'vue';

const { locale, t, localizedPath } = useI18n();
const services = computed(() => getServices(locale.value));
</script>

<template>
    <footer class="site-footer">
        <div class="footer-cta">
            <div>
                <span class="eyebrow eyebrow--gold">{{ t('footer.ready') }}</span>
                <h2>{{ t('footer.title1') }}<br><em>{{ t('footer.title2') }}</em></h2>
            </div>
            <div class="footer-cta__actions">
                <RouterLink class="button button--gold button--large" :to="localizedPath('/booking/')">
                    {{ t('footer.request') }} <AppIcon name="arrow" :size="19" />
                </RouterLink>
                <a class="text-link text-link--light" :href="`tel:${siteState.settings.phone || '+31641108800'}`">
                    {{ t('footer.callDirect') }}
                </a>
            </div>
        </div>
        <div class="footer-main">
            <div class="footer-brand">
                <SiteLogo light />
                <p>{{ t('footer.description') }}</p>
                <a :href="`mailto:${siteState.settings.email || 'info@urgent-travelservices.nl'}`">
                    {{ siteState.settings.email || 'info@urgent-travelservices.nl' }}
                </a>
            </div>
            <div class="footer-column">
                <h3>{{ t('footer.navigate') }}</h3>
                <RouterLink :to="localizedPath('/services/')">{{ t('footer.services') }}</RouterLink>
                <RouterLink :to="localizedPath('/fleet/')">{{ t('footer.fleet') }}</RouterLink>
                <RouterLink :to="localizedPath('/rates/')">{{ t('footer.rates') }}</RouterLink>
                <RouterLink :to="localizedPath('/booking/')">{{ t('footer.booking') }}</RouterLink>
                <RouterLink :to="localizedPath('/contact/')">{{ t('footer.contact') }}</RouterLink>
            </div>
            <div class="footer-column">
                <h3>{{ t('footer.popular') }}</h3>
                <RouterLink v-for="service in services.slice(0, 5)" :key="service.slug" :to="localizedPath(`/${service.slug}/`)">
                    {{ service.title }}
                </RouterLink>
            </div>
            <div class="footer-column footer-column--contact">
                <h3>Contact</h3>
                <a :href="`tel:${siteState.settings.phone || '+31641108800'}`">{{ siteState.settings.phone || '+31 6 4110 8800' }}</a>
                <p>{{ siteState.settings.address }}<br>{{ siteState.settings.postcode }} {{ siteState.settings.city }}</p>
                <span>KvK {{ siteState.settings.kvk }}</span>
                <span>{{ locale === 'nl' ? 'BTW' : 'VAT' }} {{ siteState.settings.vat }}</span>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© {{ new Date().getFullYear() }} Urgent Travel Services</span>
            <span>{{ t('footer.considered') }}</span>
            <RouterLink to="/admin/login">{{ t('footer.manager') }}</RouterLink>
        </div>
    </footer>
</template>
