import { reactive } from 'vue';
import { initialBootstrap } from './api';

export const siteState = reactive({
    settings: initialBootstrap.settings || {},
    activePage: initialBootstrap.page || null,
    loadingPage: false,
    pageError: null,
});

function upsertMeta(selector, attribute, value) {
    if (!value) return;
    let element = document.head.querySelector(selector);
    if (!element) {
        element = document.createElement('meta');
        const [key, name] = selector.replace(/[\[\]"]/g, '').split('=');
        element.setAttribute(key, name);
        document.head.appendChild(element);
    }
    element.setAttribute(attribute, value);
}

export function applySeo(page) {
    if (!page) return;
    siteState.activePage = page;
    document.title = page.seo_title || page.title;
    document.documentElement.lang = page.locale === 'nl' ? 'nl' : 'en';
    upsertMeta('meta[name="description"]', 'content', page.seo_description || page.excerpt || '');
    upsertMeta('meta[property="og:locale"]', 'content', page.locale === 'nl' ? 'nl_NL' : 'en_US');
    upsertMeta('meta[property="og:title"]', 'content', page.og_title || page.seo_title || page.title);
    upsertMeta('meta[property="og:description"]', 'content', page.og_description || page.seo_description || '');
    upsertMeta('meta[property="og:url"]', 'content', page.canonical_url || window.location.href);
    upsertMeta('meta[name="robots"]', 'content', page.robots || 'index, follow');

    if (page.og_image || page.hero_image) {
        const image = page.og_image || page.hero_image;
        upsertMeta(
            'meta[property="og:image"]',
            'content',
            image.startsWith('http') ? image : new URL(image, window.location.origin).href,
        );
    }

    let canonical = document.head.querySelector('link[rel="canonical"]');
    if (!canonical) {
        canonical = document.createElement('link');
        canonical.rel = 'canonical';
        document.head.appendChild(canonical);
    }
    canonical.href = page.canonical_url || window.location.origin + page.path;

    document.head.querySelectorAll('link[data-i18n-alternate]').forEach((link) => link.remove());
    for (const [locale, path] of Object.entries(page.alternates || {})) {
        if (!path) continue;
        const alternate = document.createElement('link');
        alternate.rel = 'alternate';
        alternate.hreflang = locale;
        alternate.href = new URL(path, window.location.origin).href;
        alternate.dataset.i18nAlternate = 'true';
        document.head.appendChild(alternate);
    }
    if (page.alternates?.nl && page.alternates?.en) {
        const fallback = document.createElement('link');
        fallback.rel = 'alternate';
        fallback.hreflang = 'x-default';
        fallback.href = new URL(page.alternates.en, window.location.origin).href;
        fallback.dataset.i18nAlternate = 'true';
        document.head.appendChild(fallback);
    }

    let schema = document.getElementById('page-schema');
    if (schema) schema.remove();
    if (page.schema_json) {
        schema = document.createElement('script');
        schema.id = 'page-schema';
        schema.type = 'application/ld+json';
        schema.textContent = JSON.stringify(page.schema_json);
        document.head.appendChild(schema);
    }
}

export function sitePhoneLink() {
    return `tel:${siteState.settings.phone || '+31641108800'}`.replace(/\s/g, '');
}

export function whatsappLink(message = '') {
    const number = String(siteState.settings.whatsapp || '31641108800').replace(/\D/g, '');
    return `https://wa.me/${number}${message ? `?text=${encodeURIComponent(message)}` : ''}`;
}

export function formatPhone() {
    return siteState.settings.phone || '+31 6 4110 8800';
}
