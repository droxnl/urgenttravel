<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import AppIcon from './AppIcon.vue';
import { api, validationMessage } from '../lib/api';
import { useI18n } from '../lib/i18n';

const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, required: true },
    placeholder: { type: String, default: '' },
    icon: { type: String, default: 'pin' },
    name: { type: String, default: 'address' },
    required: { type: Boolean, default: false },
    autocomplete: { type: String, default: 'off' },
});

const emit = defineEmits(['update:modelValue', 'select']);
const { locale, t } = useI18n();
const inputElement = ref(null);
const inputValue = ref(props.modelValue);
const suggestions = ref([]);
const open = ref(false);
const loading = ref(false);
const requestError = ref('');
const searched = ref(false);
const activeIndex = ref(-1);
const needsMoreCharacters = computed(() => inputValue.value.trim().length > 0 && inputValue.value.trim().length < 3);
const controlId = `address-${Math.random().toString(36).slice(2, 9)}`;
const listboxId = `${controlId}-suggestions`;
let debounceTimer;
let blurTimer;
let requestId = 0;

watch(
    () => props.modelValue,
    (value) => {
        if (value !== inputValue.value) inputValue.value = value || '';
    },
);

function setValue(value) {
    inputValue.value = value;
    emit('update:modelValue', value);
}

function clearSearch() {
    clearTimeout(debounceTimer);
    requestId += 1;
    loading.value = false;
    suggestions.value = [];
    requestError.value = '';
    searched.value = false;
    activeIndex.value = -1;
}

function onInput(event) {
    const value = event.target.value;
    setValue(value);
    clearSearch();
    open.value = true;

    if (value.trim().length < 3) return;

    debounceTimer = window.setTimeout(() => search(value), 420);
}

async function search(value) {
    const currentRequest = ++requestId;
    loading.value = true;
    requestError.value = '';
    searched.value = false;

    try {
        const response = await api.searchBookingAddresses(value.trim(), locale.value);
        if (currentRequest !== requestId) return;
        suggestions.value = response.data || [];
        searched.value = true;
        activeIndex.value = suggestions.value.length ? 0 : -1;
        open.value = document.activeElement === inputElement.value;
    } catch (exception) {
        if (currentRequest !== requestId) return;
        suggestions.value = [];
        requestError.value = validationMessage(exception) || t('address.failed');
        searched.value = true;
        open.value = true;
    } finally {
        if (currentRequest === requestId) loading.value = false;
    }
}

function chooseSuggestion(suggestion) {
    clearTimeout(blurTimer);
    setValue(suggestion.label);
    emit('select', suggestion);
    suggestions.value = [];
    searched.value = false;
    requestError.value = '';
    open.value = false;
    activeIndex.value = -1;
    inputElement.value?.focus();
}

function onFocus() {
    clearTimeout(blurTimer);
    if (inputValue.value.trim().length >= 3 && (suggestions.value.length || searched.value)) {
        open.value = true;
    }
}

function onBlur() {
    blurTimer = window.setTimeout(() => {
        open.value = false;
        activeIndex.value = -1;
    }, 150);
}

async function moveActive(direction) {
    if (!open.value) {
        open.value = true;
        return;
    }
    if (!suggestions.value.length) return;

    activeIndex.value = (
        activeIndex.value + direction + suggestions.value.length
    ) % suggestions.value.length;
    await nextTick();
    document.getElementById(`${listboxId}-${activeIndex.value}`)?.scrollIntoView({ block: 'nearest' });
}

function onKeydown(event) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        moveActive(1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        moveActive(-1);
    } else if (event.key === 'Enter' && open.value && activeIndex.value >= 0) {
        event.preventDefault();
        chooseSuggestion(suggestions.value[activeIndex.value]);
    } else if (event.key === 'Escape') {
        open.value = false;
        activeIndex.value = -1;
    }
}

onBeforeUnmount(() => {
    clearTimeout(debounceTimer);
    clearTimeout(blurTimer);
});
</script>

<template>
    <div class="address-autocomplete" :class="{ 'address-autocomplete--open': open }">
        <label :for="controlId">{{ label }}<template v-if="required"> *</template></label>
        <div class="address-autocomplete__control">
            <AppIcon :name="icon" :size="17" />
            <input
                :id="controlId"
                ref="inputElement"
                :value="inputValue"
                :name="name"
                type="text"
                role="combobox"
                :aria-label="label"
                :aria-expanded="open"
                :aria-controls="listboxId"
                :aria-activedescendant="activeIndex >= 0 ? `${listboxId}-${activeIndex}` : undefined"
                aria-autocomplete="list"
                :autocomplete="autocomplete"
                autocapitalize="words"
                :placeholder="placeholder"
                :required="required"
                @input="onInput"
                @focus="onFocus"
                @blur="onBlur"
                @keydown="onKeydown"
            >
            <span v-if="loading" class="address-autocomplete__spinner" :aria-label="t('address.searching')"></span>
            <AppIcon v-else-if="inputValue" class="address-autocomplete__search-icon" name="search" :size="15" />
        </div>

        <Transition name="address-results">
            <div v-if="open" class="address-autocomplete__results">
                <div v-if="loading" class="address-autocomplete__status" role="status">
                    {{ t('address.searching') }}
                </div>
                <div v-else-if="needsMoreCharacters" class="address-autocomplete__status">
                    {{ t('address.typeMore') }}
                </div>
                <div v-else-if="requestError" class="address-autocomplete__status address-autocomplete__status--error" role="alert">
                    {{ requestError }}
                </div>
                <div v-else-if="searched && !suggestions.length" class="address-autocomplete__status">
                    {{ t('address.noResults') }}
                </div>
                <ul v-else-if="suggestions.length" :id="listboxId" role="listbox" :aria-label="t('address.suggestions')">
                    <li
                        v-for="(suggestion, index) in suggestions"
                        :id="`${listboxId}-${index}`"
                        :key="suggestion.id"
                        role="option"
                        :aria-selected="activeIndex === index"
                        :class="{ active: activeIndex === index }"
                        @mouseenter="activeIndex = index"
                        @pointerdown.prevent="chooseSuggestion(suggestion)"
                    >
                        <span class="address-autocomplete__pin"><AppIcon name="pin" :size="15" /></span>
                        <span><strong>{{ suggestion.label }}</strong><small>{{ suggestion.detail }}</small></span>
                        <AppIcon name="arrow" :size="14" />
                    </li>
                </ul>
                <div class="address-autocomplete__attribution">Search by Photon · © OpenStreetMap contributors</div>
            </div>
        </Transition>
    </div>
</template>
