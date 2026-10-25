<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: { type: String, default: 'arrow' },
    size: { type: [Number, String], default: 24 },
    stroke: { type: [Number, String], default: 1.8 },
});

const icons = {
    plane: [
        'M22 2 9.5 14.5',
        'm22 2-7 20-4-9-9-4 20-7Z',
    ],
    crown: [
        'm3 7 4.5 4L12 4l4.5 7L21 7l-2 11H5L3 7Z',
        'M5 21h14',
    ],
    briefcase: [
        'M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2',
        'M4 7h16v12H4z',
        'M4 12h16',
        'M10 12v2h4v-2',
    ],
    car: [
        'M5 17h14',
        'm6 17-1-5 2-6h10l2 6-1 5',
        'M4 12h16',
        'M7 20h.01M17 20h.01',
        'M8 9h8',
    ],
    route: [
        'M4 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z',
        'M20 9a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z',
        'M6 15c0-4 3-3 6-5s4-3 6-3',
    ],
    compass: [
        'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z',
        'm16 8-2.5 5.5L8 16l2.5-5.5L16 8Z',
    ],
    clock: [
        'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z',
        'M12 6v6l4 2',
    ],
    shield: [
        'M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z',
        'm9 12 2 2 4-5',
    ],
    pin: [
        'M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z',
        'M12 12a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z',
    ],
    phone: [
        'M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.69 2.8a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.33 1.84.56 2.8.69A2 2 0 0 1 22 16.92Z',
    ],
    whatsapp: [
        'M20.5 11.6a8.5 8.5 0 0 1-12.6 7.5L3 21l1.9-4.8A8.5 8.5 0 1 1 20.5 11.6Z',
        'M8.1 7.4c.2-.4.4-.4.8-.4h.4c.2 0 .4.1.6.5l.7 1.7c.1.2 0 .4-.1.6l-.6.7c.7 1.4 1.6 2.2 3 2.9l.7-.9c.2-.2.4-.3.7-.2l1.7.8c.3.1.4.3.4.6l-.1 1.2c-.1.7-1.6 1.4-2.6 1.1-4.1-1.2-6-3.3-6.8-6.8-.2-1 .3-1.5.2-1.8Z',
    ],
    arrow: ['M5 12h14', 'm13 6 6 6-6 6'],
    mail: ['M4 4h16v16H4z', 'm4 6 8 6 8-6'],
    check: ['m5 12 4 4L19 6'],
    menu: ['M4 7h16M4 12h16M4 17h16'],
    x: ['m6 6 12 12M18 6 6 18'],
    user: ['M20 21a8 8 0 0 0-16 0', 'M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z'],
    bag: ['M6 8h12l1 13H5L6 8Z', 'M9 8V5a3 3 0 0 1 6 0v3'],
    dashboard: ['M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z'],
    pages: ['M6 2h9l4 4v16H6z', 'M14 2v5h5M9 12h6M9 16h6'],
    settings: [
        'M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z',
        'M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.12 2.12-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1 1.56V20h-3v-.08a1.7 1.7 0 0 0-1-1.56 1.7 1.7 0 0 0-1.88.34l-.06.06-2.12-2.12.06-.06A1.7 1.7 0 0 0 7.1 15a1.7 1.7 0 0 0-1.56-1H5.5v-3h.08a1.7 1.7 0 0 0 1.56-1 1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.12-2.12.06.06A1.7 1.7 0 0 0 10.8 6.3a1.7 1.7 0 0 0 1-1.56V4.6h3v.08a1.7 1.7 0 0 0 1 1.56 1.7 1.7 0 0 0 1.88-.34l.06-.06 2.12 2.12-.06.06A1.7 1.7 0 0 0 19.4 10a1.7 1.7 0 0 0 1.56 1H21v3h-.08a1.7 1.7 0 0 0-1.52 1Z',
    ],
    inbox: ['M4 4h16v16H4z', 'M4 14h4l2 3h4l2-3h4'],
    sparkles: [
        'm12 3 1.2 3.8L17 8l-3.8 1.2L12 13l-1.2-3.8L7 8l3.8-1.2L12 3Z',
        'm19 14 .8 2.2L22 17l-2.2.8L19 20l-.8-2.2L16 17l2.2-.8L19 14Z',
        'm5 14 .6 1.4L7 16l-1.4.6L5 18l-.6-1.4L3 16l1.4-.6L5 14Z',
    ],
    upload: ['M12 16V4', 'm7 9 5-5 5 5', 'M4 16v4h16v-4'],
    logout: ['M10 17l5-5-5-5', 'M15 12H3', 'M14 3h7v18h-7'],
    trash: ['M4 7h16', 'M9 7V4h6v3', 'm6 7 1 14h10l1-14', 'M10 11v6M14 11v6'],
    calendar: ['M4 5h16v16H4z', 'M8 3v4M16 3v4M4 10h16'],
    luggage: ['M6 7h12v14H6z', 'M9 7V4h6v3M9 11v6M15 11v6'],
    search: [
        'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z',
        'm21 21-4.35-4.35',
    ],
    chevron: ['m8 10 4 4 4-4'],
};

const paths = computed(() => icons[props.name] || icons.arrow);
const isFilled = computed(() => false);
</script>

<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        :fill="isFilled ? 'currentColor' : 'none'"
        :stroke="isFilled ? 'none' : 'currentColor'"
        :stroke-width="stroke"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <path v-for="path in paths" :key="path" :d="path" />
    </svg>
</template>
