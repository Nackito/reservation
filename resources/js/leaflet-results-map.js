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
    // Attendre que le conteneur ait une taille visible pour éviter un canvas blanc
    const rect = el.getBoundingClientRect();
    if ((rect.width === 0 || rect.height === 0) && !el._leaflet_waitSize) {
        el._leaflet_waitSize = true;
        let attempts = 0;
        const t = setInterval(() => {
            const r = el.getBoundingClientRect();
            if (r.width > 0 && r.height > 0) {
                clearInterval(t);
                el._leaflet_waitSize = false;
                initResultsMap(el);
            } else if (++attempts > 15) {
                // ~3s max
                clearInterval(t);
                el._leaflet_waitSize = false;
            }
        }, 200);
        return;
    }
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
            // Icône avec prix: badge au-dessus du pin
            const priceBadge = m.priceText
                ? `<div class=\"price-badge\">${m.priceText}</div>`
                : "";
            const composedIcon = L.divIcon({
                className: "price-marker",
                html: `${priceBadge}<img class=\"pin\" src=\"${markerIcon}\" alt=\"pin\"/>`,
                iconSize: [30, 46],
                iconAnchor: [15, 46],
                popupAnchor: [0, -46],
            });
            const marker = L.marker([m.lat, m.lng], {
                icon: composedIcon,
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

        // Cadrage auto si demandé (par défaut oui, mais le serveur peut l'ignorer)
        if ((data.fitBounds ?? true) && markers.length > 1) {
            const group = L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.1));
        } else if (data.center) {
            map.setView([data.center.lat, data.center.lng], data.zoom || 12);
        }

        const invalidate = () => {
            try {
                map.invalidateSize();
            } catch (_) {}
        };
        // multiples invalidations pour couvrir les transitions et le morphing
        requestAnimationFrame(invalidate);
        setTimeout(invalidate, 150);
        setTimeout(invalidate, 400);
        setTimeout(invalidate, 900);
        window.addEventListener("resize", invalidate, { passive: true });

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
                const priceBadge = m.priceText
                    ? `<div class=\"price-badge\">${m.priceText}</div>`
                    : "";
                const composedIcon = L.divIcon({
                    className: "price-marker",
                    html: `${priceBadge}<img class=\"pin\" src=\"${markerIcon}\" alt=\"pin\"/>`,
                    iconSize: [30, 46],
                    iconAnchor: [15, 46],
                    popupAnchor: [0, -46],
                });
                const marker = L.marker([m.lat, m.lng], {
                    icon: composedIcon,
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
            if ((data.fitBounds ?? true) && markers.length > 1) {
                const group = L.featureGroup(markers);
                el._leaflet_map.fitBounds(group.getBounds().pad(0.1));
            } else if (data.center) {
                el._leaflet_map.setView(
                    [data.center.lat, data.center.lng],
                    data.zoom || 12
                );
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

    // Petits retries juste après l'apparition pour couvrir les cas de layout async
    let tries = 10;
    const bootRetries = setInterval(() => {
        const el = document.getElementById("results-map");
        if (el && el._leaflet_map) {
            clearInterval(bootRetries);
            return;
        }
        tryInit();
        if (--tries <= 0) clearInterval(bootRetries);
    }, 200);

    // MutationObserver pour détecter l'apparition du conteneur ou le changement de data-map
    const observer = new MutationObserver((mutations) => {
        let shouldTry = false;
        for (const m of mutations) {
            if (m.type === "childList") {
                // Rechercher #results-map dans tout le sous-arbre ajouté
                for (const node of m.addedNodes) {
                    if (node.nodeType === 1) {
                        if (
                            node.id === "results-map" ||
                            node.querySelector?.("#results-map")
                        ) {
                            shouldTry = true;
                            break;
                        }
                    }
                }
            } else if (m.type === "attributes") {
                if (
                    m.target.id === "results-map" &&
                    m.attributeName === "data-map"
                ) {
                    shouldTry = true;
                }
            }
            if (shouldTry) break;
        }
        if (shouldTry) {
            // Throttle via rAF
            requestAnimationFrame(tryInit);
        }
    });
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ["data-map"],
    });

    // Hooks Livewire
    document.addEventListener("livewire:init", () => {
        tryInit();
        if (window.Livewire && Livewire.hook) {
            Livewire.hook("morph.updated", tryInit);
            Livewire.hook("message.processed", tryInit);
        }
    });
    document.addEventListener("livewire:load", tryInit);
    document.addEventListener("livewire:navigated", tryInit);
    // Au cas où Livewire.hook est déjà présent avant livewire:init
    if (window.Livewire && Livewire.hook) {
        Livewire.hook("morph.updated", tryInit);
        Livewire.hook("message.processed", tryInit);
    }

    // Notre évènement maison (émis après search/filters côté Livewire)
    window.addEventListener("refresh-carousels", () => {
        // Appeler plusieurs fois avec délais pour s’aligner sur le morphing Livewire
        tryInit();
        setTimeout(tryInit, 0);
        setTimeout(tryInit, 150);
        setTimeout(tryInit, 400);
        setTimeout(tryInit, 900);
    });

    // Invalidation à la fin d’une transition CSS du conteneur
    document.addEventListener("transitionend", (e) => {
        const el = document.getElementById("results-map");
        if (!el || !el._leaflet_map) return;
        if (e.target === el || el.contains(e.target)) {
            try {
                el._leaflet_map.invalidateSize();
            } catch (_) {}
        }
    });
}

// Lancer quand DOM prêt
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", watchResultsMap);
} else {
    watchResultsMap();
}
