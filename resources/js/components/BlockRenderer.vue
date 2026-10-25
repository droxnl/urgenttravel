<script setup>
import { computed } from 'vue';
import AppIcon from './AppIcon.vue';
import { useI18n } from '../lib/i18n';

const props = defineProps({
    block: { type: Object, required: true },
});

const { localizedPath } = useI18n();
const content = computed(() => props.block.content || {});
const items = computed(() => content.value.items || []);
</script>

<template>
    <section v-if="block.type === 'trust_strip'" class="trust-strip">
        <div class="container trust-strip__inner">
            <div v-for="item in items" :key="`${item.icon}-${item.title}`" class="trust-point">
                <span class="trust-point__icon"><AppIcon :name="item.icon || 'check'" :size="22" /></span>
                <div><strong>{{ item.title }}</strong><span>{{ item.text }}</span></div>
            </div>
        </div>
    </section>

    <section v-else-if="block.type === 'service_grid'" class="services-section section">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">{{ content.eyebrow }}</span>
                    <h2>{{ content.heading_line_1 }}<br><em>{{ content.heading_line_2 }}</em></h2>
                </div>
                <p>{{ content.intro }}</p>
            </div>
            <div class="service-grid">
                <RouterLink
                    v-for="(item, index) in items"
                    :key="`${item.link}-${index}`"
                    :to="localizedPath(item.link)"
                    class="service-card"
                >
                    <span class="service-card__number">{{ String(index + 1).padStart(2, '0') }}</span>
                    <span class="service-card__icon"><AppIcon :name="item.icon || 'arrow'" :size="26" /></span>
                    <span class="service-card__kicker">{{ item.kicker }}</span>
                    <h3>{{ item.title }}</h3>
                    <p>{{ item.description }}</p>
                    <span class="service-card__link">{{ content.link_label }} <AppIcon name="arrow" :size="17" /></span>
                </RouterLink>
            </div>
        </div>
    </section>

    <section v-else-if="block.type === 'fleet_grid'" class="fleet-section section">
        <div class="container">
            <div class="fleet-heading">
                <span class="eyebrow eyebrow--gold">{{ content.eyebrow }}</span>
                <h2>{{ content.heading_line_1 }}<br><em>{{ content.heading_line_2 }}</em></h2>
                <p>{{ content.intro }}</p>
                <RouterLink v-if="content.link" class="text-link text-link--light" :to="localizedPath(content.link)">
                    {{ content.link_label }} <AppIcon name="arrow" :size="17" />
                </RouterLink>
            </div>
            <div class="fleet-grid">
                <article v-for="(item, index) in items" :key="`${item.title}-${index}`" class="fleet-card">
                    <div class="fleet-card__image">
                        <img :src="item.image" :alt="item.title" loading="lazy">
                        <span>{{ String(index + 1).padStart(2, '0') }}</span>
                    </div>
                    <div class="fleet-card__body">
                        <span class="fleet-card__label">{{ item.label }}</span>
                        <h3>{{ item.title }}</h3>
                        <p>{{ item.description }}</p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section v-else-if="block.type === 'coverage_grid'" class="coverage-section section">
        <div class="container">
            <div class="coverage-heading">
                <div>
                    <span class="eyebrow eyebrow--gold">{{ content.eyebrow }}</span>
                    <h2>{{ content.heading_line_1 }}<br><em>{{ content.heading_line_2 }}</em></h2>
                </div>
                <p>{{ content.intro }}</p>
            </div>
            <div class="coverage-grid">
                <div v-for="(group, index) in items" :key="`${group.title}-${index}`" class="province-card">
                    <span class="province-card__index">{{ String(index + 1).padStart(2, '0') }}</span>
                    <h3>{{ group.title }}</h3>
                    <div class="province-card__cities">
                        <RouterLink v-for="(link, linkIndex) in group.links" :key="`${link.url}-${linkIndex}`" :to="localizedPath(link.url)">
                            {{ link.label }} <AppIcon name="arrow" :size="14" />
                        </RouterLink>
                    </div>
                </div>
            </div>
            <div class="coverage-footer">
                <span><AppIcon name="plane" :size="19" /> {{ content.footer_note }}</span>
                <RouterLink class="text-link" :to="localizedPath(content.cta_link)">{{ content.cta_label }} <AppIcon name="arrow" :size="16" /></RouterLink>
            </div>
        </div>
    </section>

    <section v-else-if="block.type === 'related_links'" class="related-services section">
        <div class="container">
            <div class="section-heading section-heading--small">
                <div><span class="eyebrow">{{ content.eyebrow }}</span><h2>{{ content.heading }}</h2></div>
            </div>
            <div class="related-links">
                <RouterLink v-for="(item, index) in items" :key="`${item.link}-${index}`" :to="localizedPath(item.link)">
                    <span>{{ item.title }}</span><AppIcon name="arrow" :size="18" />
                </RouterLink>
            </div>
        </div>
    </section>
</template>
