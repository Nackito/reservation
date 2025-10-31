import L from "leaflet";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";
import "leaflet/dist/leaflet.css";

// Configurer les icônes via les assets bundlés (ESM)
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

// Exposer Leaflet globalement par sécurité
window.L = L;

// Icône explicite pour la page Booking (évite tout conflit d'instances)
const bookingMarkerIcon = L.icon({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41],
});

function init(lat, lng, label = "Résidence") {
    // Si lat/lng non fournis, centrer sur la Côte d'Ivoire
    const centerLat = typeof lat === "number" && !isNaN(lat) ? lat : 7.54;
    const centerLng = typeof lng === "number" && !isNaN(lng) ? lng : -5.55;
    const zoom =
        typeof lat === "number" &&
        !isNaN(lat) &&
        typeof lng === "number" &&
        !isNaN(lng)
            ? 17
            : 11;

    const map = L.map("map").setView([centerLat, centerLng], zoom);
    const tilesUrl =
        typeof import.meta !== "undefined" &&
        import.meta.env &&
        import.meta.env.VITE_MAP_TILES_URL
            ? import.meta.env.VITE_MAP_TILES_URL
            : typeof import.meta !== "undefined" &&
              import.meta.env &&
              import.meta.env.VITE_MAP_TILER_KEY
            ? `https://api.maptiler.com/maps/streets-v2/256/{z}/{x}/{y}.png?key=${
                  import.meta.env.VITE_MAP_TILER_KEY
              }`
            : "https://tile.openstreetmap.org/{z}/{x}/{y}.png"; // fallback (peut être bloqué sur APK)

    const tilesAttribution =
        typeof import.meta !== "undefined" &&
        import.meta.env &&
        import.meta.env.VITE_MAP_TILES_ATTRIBUTION
            ? import.meta.env.VITE_MAP_TILES_ATTRIBUTION
            : "&copy; MapTiler &copy; OpenStreetMap contributors";

    const layerOptions = { maxZoom: 20, attribution: tilesAttribution };
    L.tileLayer(tilesUrl, layerOptions).addTo(map);

    if (
        typeof lat === "number" &&
        !isNaN(lat) &&
        typeof lng === "number" &&
        !isNaN(lng)
    ) {
        const marker = L.marker([lat, lng], {
            icon: bookingMarkerIcon,
            title: label,
            alt: "Emplacement",
        }).addTo(map);

        const glat = Number(lat).toFixed(6);
        const glng = Number(lng).toFixed(6);
        const esc = (s) =>
            String(s || "").replace(
                /[&<>"']/g,
                (c) =>
                    ({
                        "&": "&amp;",
                        "<": "&lt;",
                        ">": "&gt;",
                        '"': "&quot;",
                        "'": "&#39;",
                    }[c])
            );
        const googleMapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${glat},${glng}`;
        const wazeUrl = `https://waze.com/ul?ll=${glat},${glng}&navigate=yes`;
        const appleMapsUrl = `https://maps.apple.com/?daddr=${glat},${glng}&dirflg=d`;
        const geoUrl = `geo:${glat},${glng}?q=${glat},${glng}(${encodeURIComponent(
            label || "Destination"
        )})`;

        const popupHtml = `
            <div class="text-sm">
                <div class="font-semibold mb-2 text-gray-800 dark:text-gray-100">${esc(
                    label
                )}</div>
                <div class="flex flex-col gap-1">
                    <a class="inline-flex items-center px-2 py-1 rounded border border-blue-200 text-blue-700 bg-blue-50 hover:bg-blue-100 dark:border-blue-800 dark:text-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/40" href="${googleMapsUrl}" target="_blank" rel="noopener">Ouvrir dans Google Maps</a>
                    <a class="inline-flex items-center px-2 py-1 rounded border border-purple-200 text-purple-700 bg-purple-50 hover:bg-purple-100 dark:border-purple-800 dark:text-purple-200 dark:bg-purple-900/30 dark:hover:bg-purple-900/40" href="${wazeUrl}" target="_blank" rel="noopener">Ouvrir dans Waze</a>
                    <a class="inline-flex items-center px-2 py-1 rounded border border-gray-200 text-gray-700 bg-gray-50 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:bg-gray-800/60 dark:hover:bg-gray-800" href="${appleMapsUrl}" target="_blank" rel="noopener">Ouvrir dans Apple Maps</a>
                </div>
            </div>
        `;

        marker.bindPopup(popupHtml, { maxWidth: 260 });
        marker.bindTooltip(label, { direction: "top", offset: [0, -12] });
    }

    // Sécurise le rendu si le conteneur a été caché/affiché
    setTimeout(() => {
        try {
            map.invalidateSize();
        } catch (_) {}
    }, 150);
}

// Rendre la fonction accessible globalement pour les scripts inline (Vite)
window.init = init;

//init();
