<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    route: { type: Object, required: true },
    labels: {
        type: Object,
        default: () => ({
            pickup: 'Pickup',
            dropoff: 'Drop-off',
            map: 'Calculated driving route',
            unavailable: 'The route map is temporarily unavailable.',
        }),
    },
});

const mapElement = ref(null);
const mapFailed = ref(false);
let map;
let routeLayer;
let markerLayer;

function renderRoute() {
    if (!map || !props.route) return;

    routeLayer?.clearLayers();
    markerLayer?.clearLayers();

    const geometry = props.route.geometry || [];
    const pickup = props.route.pickup;
    const dropoff = props.route.dropoff;

    if (geometry.length > 1) {
        const points = geometry.map(([lat, lng]) => [lat, lng]);
        const polyline = L.polyline(points, {
            color: '#d2a24c',
            weight: 5,
            opacity: 0.95,
            lineCap: 'round',
        });
        routeLayer.addLayer(polyline);
        L.circleMarker(points[0], {
            radius: 7,
            color: '#fffefa',
            weight: 3,
            fillColor: '#111113',
            fillOpacity: 1,
        }).bindTooltip(props.labels.pickup).addTo(markerLayer);
        L.circleMarker(points[points.length - 1], {
            radius: 7,
            color: '#fffefa',
            weight: 3,
            fillColor: '#d2a24c',
            fillOpacity: 1,
        }).bindTooltip(props.labels.dropoff).addTo(markerLayer);
        map.fitBounds(polyline.getBounds(), { padding: [35, 35] });
    } else if (pickup) {
        const point = [pickup.latitude, pickup.longitude];
        L.circleMarker(point, {
            radius: 8,
            color: '#fffefa',
            weight: 3,
            fillColor: '#111113',
            fillOpacity: 1,
        }).bindTooltip(props.labels.pickup).addTo(markerLayer);
        map.setView(point, 12);
    } else if (dropoff) {
        map.setView([dropoff.latitude, dropoff.longitude], 12);
    }

    window.setTimeout(() => map?.invalidateSize(), 100);
}

onMounted(() => {
    if (!mapElement.value) return;

    try {
        map = L.map(mapElement.value, {
            zoomControl: true,
            scrollWheelZoom: false,
            attributionControl: true,
        }).setView([52.3676, 4.9041], 9);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);
        routeLayer = L.layerGroup().addTo(map);
        markerLayer = L.layerGroup().addTo(map);
        renderRoute();
    } catch (exception) {
        console.error('Unable to initialise the booking route map.', exception);
        mapFailed.value = true;
    }
});

watch(() => props.route, renderRoute, { deep: true });

onBeforeUnmount(() => {
    map?.remove();
    map = null;
});
</script>

<template>
    <div class="route-map-shell">
        <div v-if="!mapFailed" ref="mapElement" class="route-map" :aria-label="labels.map"></div>
        <div v-else class="route-map route-map--failed">{{ labels.unavailable }}</div>
    </div>
</template>
