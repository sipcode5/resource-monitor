import axios from "axios";
import Echo from "laravel-echo";
import Pusher from "pusher-js";

// ── Axios global config ────────────────────────────────────────────────────
window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
window.axios.defaults.headers.common["X-CSRF-TOKEN"] =
    document.querySelector('meta[name="csrf-token"]')?.content ?? "";

// ── Laravel Echo / Reverb setup ────────────────────────────────────────────
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8000,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8000,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "http") === "https",
    enabledTransports: ["ws", "wss"],
});
