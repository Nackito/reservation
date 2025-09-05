import Echo from "laravel-echo";

import Pusher from "pusher-js";
window.Pusher = Pusher;

const scheme = import.meta.env.VITE_REVERB_SCHEME || "http";
const wsHost = import.meta.env.VITE_REVERB_HOST;
const wsPort =
    import.meta.env.VITE_REVERB_PORT || (scheme === "https" ? 443 : 80);

window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: wsHost,
    wsPort: wsPort,
    wssPort: wsPort,
    forceTLS: scheme === "http",
    enabledTransports: scheme === "http" ? ["wss"] : ["ws"],
});
