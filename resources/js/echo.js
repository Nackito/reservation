import Echo from "laravel-echo";

// Pusher connector (recommandé en hébergement mutualisé OVH Pro)
import Pusher from "pusher-js";
window.Pusher = Pusher;

// Variables Vite attendues côté front pour Pusher
// VITE_PUSHER_APP_KEY, VITE_PUSHER_APP_CLUSTER (ex: mt1, eu),
// VITE_PUSHER_HOST (optionnel), VITE_PUSHER_PORT (80/443), VITE_PUSHER_SCHEME (http/https)
const cluster = import.meta.env.VITE_PUSHER_APP_CLUSTER || "mt1";
const scheme = import.meta.env.VITE_PUSHER_SCHEME || "https";
const useTLS = scheme === "https";
const wsHost = import.meta.env.VITE_PUSHER_HOST || `ws-${cluster}.pusher.com`;
const wsPort = Number(import.meta.env.VITE_PUSHER_PORT || (useTLS ? 443 : 80));

// Récupère le token CSRF depuis <meta> ou cookie XSRF-TOKEN (compatible Filament)
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.getAttribute("content"))
        return meta.getAttribute("content");
    const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : "";
}
const csrfToken = getCsrfToken();
const appKey = import.meta.env.VITE_PUSHER_APP_KEY;

// Ne pas initialiser si aucune clé n'est fournie par Vite (évite l'erreur Pusher)
if (!appKey) {
    console.warn("[Echo] Skipped init: VITE_PUSHER_APP_KEY manquante.");
} else if (window.Echo) {
    // Evite une double initialisation si un autre script a déjà créé Echo
    console.info("[Echo] Instance existante détectée, init ignorée.");
} else {
    window.Echo = new Echo({
        broadcaster: "pusher",
        key: appKey,
        cluster,
        wsHost,
        wsPort,
        wssPort: wsPort,
        forceTLS: useTLS,
        // Laisser les deux pour compatibilité; Pusher choisit wss en TLS
        enabledTransports: ["ws", "wss"],
        // Optionnel: réduit le bruit réseau de Pusher
        disableStats: true,
        // Authentification des canaux privés (ajoute le CSRF)
        authEndpoint: "/broadcasting/auth",
        auth: {
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "X-Requested-With": "XMLHttpRequest",
            },
            withCredentials: true,
        },
    });
}
