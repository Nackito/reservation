// Initialisation globale de la carte des résultats (#results-map)
// Fonctionne avec Livewire v3, Alpine morph, et DOM dynamique
import L from "leaflet";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";
import "leaflet/dist/leaflet.css";

// Fix des icônes par défaut quand on bundle avec Vite
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

// Icône personnalisée (plus robuste et explicite)
const resultsMarkerIcon = L.icon({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41],
});

// Exposer L globalement si nécessaire (compat avec anciens scripts)
if (!window.L) window.L = L;

function parseMapData(el) {
    try {
        const raw = el.getAttribute("data-map") || "{}";
        return JSON.parse(raw);
    } catch (e) {
        console.warn("Invalid map data", e);
        return null;
    }
}

function initResultsMap(el) {
    if (!window.L || !el || el.dataset.inited === "1") return;
    const data = parseMapData(el);
    if (!data || !data.center) return;

    try {
        const map = L.map(el).setView(
            [data.center.lat, data.center.lng],
            data.zoom || 12
        );
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: "&copy; OpenStreetMap",
        }).addTo(map);

        const markers = [];
        (data.markers || []).forEach((m) => {
            const marker = L.marker([m.lat, m.lng], {
                icon: resultsMarkerIcon,
            }).addTo(map);
            const city = m.city || "";
            const muni = m.municipality ? ", " + m.municipality : "";
            const html = `<div class="text-sm"><strong>${
                m.title || "Hébergement"
            }</strong><br>${city}${muni}<br><a class="text-blue-600" href="${
                m.url
            }">Voir</a></div>`;
            marker.bindPopup(html);
            marker.bindTooltip(m.title || "Hébergement", {
                direction: "top",
                offset: [0, -12],
            });
            markers.push(marker);
        });

        // Cadrage auto
        if (markers.length > 1) {
            const group = L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.1));
        }

        setTimeout(() => {
            try {
                map.invalidateSize();
            } catch (_) {}
        }, 200);

        el.dataset.inited = "1";
        el._leaflet_map = map;
    } catch (e) {
        console.warn("Map init error", e);
    }
}

function updateOrInitResultsMap(el) {
    const data = parseMapData(el);
    if (!data) return;
    if (el._leaflet_map) {
        try {
            el._leaflet_map.setView(
                [data.center.lat, data.center.lng],
                data.zoom || 12
            );
            // Suppression des anciens marqueurs si on veut mettre à jour
            const layerToRemove = [];
            el._leaflet_map.eachLayer((layer) => {
                if (layer instanceof L.Marker) layerToRemove.push(layer);
            });
            layerToRemove.forEach((l) => el._leaflet_map.removeLayer(l));
            const markers = [];
            (data.markers || []).forEach((m) => {
                const marker = L.marker([m.lat, m.lng], {
                    icon: resultsMarkerIcon,
                }).addTo(el._leaflet_map);
                const city = m.city || "";
                const muni = m.municipality ? ", " + m.municipality : "";
                const html = `<div class="text-sm"><strong>${
                    m.title || "Hébergement"
                }</strong><br>${city}${muni}<br><a class="text-blue-600" href="${
                    m.url
                }">Voir</a></div>`;
                marker.bindPopup(html);
                marker.bindTooltip(m.title || "Hébergement", {
                    direction: "top",
                    offset: [0, -12],
                });
                markers.push(marker);
            });
            if (markers.length > 1) {
                const group = L.featureGroup(markers);
                el._leaflet_map.fitBounds(group.getBounds().pad(0.1));
            }
            setTimeout(() => {
                try {
                    el._leaflet_map.invalidateSize();
                } catch (_) {}
            }, 100);
        } catch (e) {
            console.warn("Map update error", e);
        }
    } else {
        initResultsMap(el);
    }
}

function watchResultsMap() {
    const tryInit = () => {
        const el = document.getElementById("results-map");
        if (el) updateOrInitResultsMap(el);
    };

    // Premier essai au chargement
    tryInit();

    // MutationObserver pour détecter l'apparition du conteneur ou le changement de data-map
    const observer = new MutationObserver((mutations) => {
        for (const m of mutations) {
            if (m.type === "childList") {
                if ([...m.addedNodes].some((n) => n.id === "results-map")) {
                    tryInit();
                }
            }
            if (
                m.type === "attributes" &&
                m.target.id === "results-map" &&
                m.attributeName === "data-map"
            ) {
                tryInit();
            }
        }
    });
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ["data-map"],
    });

    // Hooks Livewire
    document.addEventListener("livewire:init", tryInit);
    document.addEventListener("livewire:load", tryInit);
    document.addEventListener("livewire:navigated", tryInit);
    if (window.Livewire && Livewire.hook) {
        Livewire.hook("morph.updated", tryInit);
    }

    // Notre évènement maison
    window.addEventListener("refresh-carousels", tryInit);
}

// Lancer quand DOM prêt
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", watchResultsMap);
} else {
    watchResultsMap();
}
