import { ref, onMounted, onUnmounted } from "vue";

const MAX_ALERTS = 50;

/**
 * Composable that listens on the `notifications` channel and accumulates
 * live alerts. Alerts auto-expire after `ttl` milliseconds.
 */
export function useNotifications(ttl = 8000) {
    const alerts = ref([]);
    let channel = null;
    let idSeq = 0;

    function addAlert(payload) {
        const id = ++idSeq;
        alerts.value = [{ id, ...payload }, ...alerts.value].slice(
            0,
            MAX_ALERTS,
        );

        // Auto-dismiss info / warning after `ttl`; critical stays until dismissed
        if (payload.severity !== "critical") {
            setTimeout(() => dismiss(id), ttl);
        }
    }

    function dismiss(id) {
        alerts.value = alerts.value.filter((a) => a.id !== id);
    }

    function dismissAll() {
        alerts.value = [];
    }

    function subscribe() {
        if (!window.Echo) return;

        channel = window.Echo.channel("notifications").listen(
            ".alert.triggered",
            addAlert,
        );
    }

    onMounted(subscribe);

    onUnmounted(() => {
        channel?.stopListening(".alert.triggered");
        window.Echo?.leave("notifications");
    });

    return { alerts, dismiss, dismissAll };
}
