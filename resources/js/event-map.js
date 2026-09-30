import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
const instances = new Map();
function mountAll() {
    for (const [element, map] of instances) if (!element.isConnected) { map.remove(); instances.delete(element); }
    document.querySelectorAll('[data-event-plan]').forEach(element => {
        if (instances.has(element)) return;
        const data = JSON.parse(element.querySelector('[data-plan-json]').textContent);
        const canvas = element.querySelector('[data-plan-canvas]');
        const map = L.map(canvas, { crs: L.CRS.Simple, minZoom: -5, maxZoom: 5, attributionControl: false });
        instances.set(element, map);
        const bounds = [[0, 0], [data.height, data.width]];
        L.imageOverlay(data.image, bounds).addTo(map); map.fitBounds(bounds);
        const position = p => [data.height * (1 - p[1] / 100), data.width * p[0] / 100];
        const colors = { public: '#2f855a', qualified: '#b7791f', restricted: '#a33e42' };
        for (const terrace of data.terraces) {
            const label = document.createElement('span'); label.textContent = terrace.name;
            L.polygon(terrace.boundary.map(position), { color: colors[terrace.access] || '#17523d', weight: 2 }).bindTooltip(label).addTo(map);
        }
        for (const activity of data.activities) {
            const popup = document.createElement(activity.url ? 'a' : 'span'); popup.textContent = activity.name;
            if (activity.url) popup.href = activity.url;
            L.circleMarker(position([activity.x, activity.y]), { radius: 9, color: '#173d32', fillColor: '#e4c36a', fillOpacity: 1 }).bindPopup(popup).addTo(map);
        }
        const editor = element.closest('[data-plan-editor]');
        if (editor) {
            let mode = null, points = [], draft = null;
            const status = editor.querySelector('[data-plan-status]');
            const terraceSelect = editor.querySelector('[data-terrace]');
            const name = editor.querySelector('[data-terrace-name]');
            const component = () => { let root = editor; while (root && !root.hasAttribute('wire:id')) root = root.parentElement; return window.Livewire.find(root.getAttribute('wire:id')); };
            const redraw = () => { if (draft) map.removeLayer(draft); draft = points.length ? L.polyline(points.map(position), { color: '#3366dd', dashArray: '5 5' }).addTo(map) : null; };
            terraceSelect.addEventListener('change', () => { name.value = data.terraces.find(t => t.id === terraceSelect.value)?.name || ''; });
            editor.querySelector('[data-draw]').onclick = () => { mode = 'draw'; points = []; redraw(); status.textContent = 'Cliquez sur chaque sommet, puis enregistrez le tracé.'; };
            editor.querySelector('[data-undo]').onclick = () => { points.pop(); redraw(); };
            editor.querySelector('[data-place]').onclick = () => { mode = 'place'; status.textContent = 'Cliquez dans la terrasse sélectionnée pour placer cette épreuve.'; };
            editor.querySelector('[data-save]').onclick = async () => {
                if (points.length < 3 || !name.value.trim()) { status.textContent = 'Donnez un nom et au moins trois sommets.'; return; }
                status.textContent = 'Enregistrement…';
                try { await component().call('saveTerrace', name.value.trim(), points, terraceSelect.value || null); } catch { status.textContent = 'Tracé non enregistré : vérifiez les erreurs du formulaire.'; }
            };
            map.on('click', async event => {
                const x = event.latlng.lng / data.width * 100, y = (1 - event.latlng.lat / data.height) * 100;
                if (x < 0 || x > 100 || y < 0 || y > 100) { status.textContent = 'Cliquez à l’intérieur du fond de plan.'; return; }
                if (mode === 'draw') { if (points.length >= 80) return; points.push([x, y]); redraw(); }
                if (mode === 'place') {
                    const activity = editor.querySelector('[data-activity]').value;
                    if (!activity || !terraceSelect.value) { status.textContent = 'Choisissez une épreuve et sa terrasse.'; return; }
                    mode = null; status.textContent = 'Enregistrement…';
                    try { await component().call('placeActivity', activity, terraceSelect.value, x, y); } catch { status.textContent = 'Placement non enregistré : vérifiez les erreurs du formulaire.'; }
                }
            });
        }
        requestAnimationFrame(() => map.invalidateSize());
    });
}
mountAll();
document.addEventListener('DOMContentLoaded', mountAll);
document.addEventListener('livewire:navigated', mountAll);
window.addEventListener('qapas-map-mount', mountAll);
