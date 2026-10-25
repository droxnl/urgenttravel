import { onMounted, ref } from 'vue';
import { api, initialBootstrap, validationMessage } from '../lib/api';
import { localeFromPath, message } from '../lib/i18n';
import { applySeo, siteState } from '../lib/seo';

export function usePage(pathResolver) {
    const page = ref(initialBootstrap.page || null);
    const loading = ref(false);
    const error = ref(null);

    async function load() {
        const path = typeof pathResolver === 'function' ? pathResolver() : pathResolver;
        if (page.value?.path === path) {
            applySeo(page.value);
            return page.value;
        }

        loading.value = true;
        siteState.loadingPage = true;
        error.value = null;
        try {
            page.value = await api.getPage(path);
            applySeo(page.value);
            return page.value;
        } catch (exception) {
            const locale = localeFromPath(path);
            error.value = exception?.response?.status === 404
                ? message(locale, 'error.pageNotFound')
                : validationMessage(exception);
            throw exception;
        } finally {
            loading.value = false;
            siteState.loadingPage = false;
        }
    }

    onMounted(() => {
        load().catch(() => {});
    });

    return { page, loading, error, load };
}
