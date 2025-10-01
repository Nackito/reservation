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
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution:
            '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

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
        marker.bindPopup(label);
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
