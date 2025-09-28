import axios from "axios";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
// Joindre automatiquement le token CSRF aux requêtes POST (dont /broadcasting/auth)
const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
if (csrfTokenMeta) {
    window.axios.defaults.headers.common["X-CSRF-TOKEN"] =
        csrfTokenMeta.getAttribute("content");
}

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import "./echo";
