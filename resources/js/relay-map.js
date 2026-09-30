import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
const maps = new Map();
function mount() {
    for (const [el, map] of maps) if (!el.isConnected) { map.remove(); maps.delete(el); }
    document.querySelectorAll('[data-relay-map]').forEach(el => {
        if (maps.has(el)) return;
        const points = JSON.parse(el.querySelector('[data-relay-json]').textContent);
        if (!points.length) return;
        const map = L.map(el.querySelector('[data-relay-canvas]')); maps.set(el, map);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>' }).addTo(map);
        const bounds = [];
        for (const p of points) {
            if (!Number.isFinite(p.lat) || !Number.isFinite(p.lng)) continue;
            const label = document.createElement('a'); label.textContent = p.name; label.href = '#' + p.anchor;
            L.circleMarker([p.lat, p.lng], { radius: 9, color: '#17523d', fillColor: '#e4c36a', fillOpacity: 1 }).bindPopup(label).addTo(map);
            bounds.push([p.lat, p.lng]);
        }
        if (bounds.length) map.fitBounds(bounds, { padding: [35, 35], maxZoom: 15 });
    });
}
mount(); document.addEventListener('DOMContentLoaded', mount); document.addEventListener('livewire:navigated', mount);
