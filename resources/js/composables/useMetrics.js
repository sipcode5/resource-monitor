import { ref, onMounted, onUnmounted } from "vue";
import axios from "axios";

/**
 * Composable that connects to the public `metrics` channel via Reverb/Echo
 * and exposes reactive system metrics. Falls back to HTTP polling when
 * the WebSocket is unavailable.
 */
export function useMetrics() {
    const metrics = ref(null);
    const connected = ref(false);
    const loading = ref(true);
    const error = ref(null);
    const history = ref([]); // rolling window of last 20 samples

    let channel = null;
    let pollTimer = null;

    // ── Fetch initial snapshot via HTTP ──────────────────────────────────
    async function fetchSnapshot() {
        try {
            const { data } = await axios.get("/api/metrics");
            if (data && data.timestamp) {
                applyMetrics(data);
            }
        } catch (e) {
            error.value = "Could not load initial metrics.";
        } finally {
            loading.value = false;
        }
    }

    function applyMetrics(data) {
        metrics.value = data;
        // keep a rolling history for sparklines / charts
        history.value = [
            ...history.value.slice(-19),
            {
                ts: data.timestamp,
                cpu: data.cpu_percent ?? 0,
                mem: data.memory?.used_percent ?? 0,
                disk: data.disk?.used_percent ?? 0,
            },
        ];
    }

    // ── WebSocket subscription ────────────────────────────────────────────
    function subscribe() {
        if (!window.Echo) return;

        channel = window.Echo.channel("metrics")
            .listen(".metrics.updated", (data) => {
                connected.value = true;
                loading.value = false;
                applyMetrics(data);
            })
            .error(() => {
                connected.value = false;
                startPolling();
            });

        // Detect connection state from Reverb connector
        window.Echo.connector?.pusher?.connection?.bind("connected", () => {
            connected.value = true;
        });
        window.Echo.connector?.pusher?.connection?.bind("disconnected", () => {
            connected.value = false;
        });
    }

    // ── HTTP polling fallback (every 10 s) ───────────────────────────────
    function startPolling() {
        if (pollTimer) return;
        pollTimer = setInterval(fetchSnapshot, 10_000);
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    // ── Trigger an immediate refresh ─────────────────────────────────────
    async function refresh() {
        loading.value = true;
        await axios.post("/api/metrics/refresh");
        // The response will arrive via WebSocket; reset loading after 3 s if nothing arrives
        setTimeout(() => {
            loading.value = false;
        }, 3000);
    }

    onMounted(() => {
        fetchSnapshot();
        subscribe();
    });

    onUnmounted(() => {
        channel?.stopListening(".metrics.updated");
        window.Echo?.leave("metrics");
        stopPolling();
    });

    return { metrics, connected, loading, error, history, refresh };
}
