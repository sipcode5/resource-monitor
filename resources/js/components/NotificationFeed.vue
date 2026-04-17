<template>
  <div class="flex flex-col gap-2">
    <div class="flex items-center justify-between">
      <h3 class="text-sm font-semibold uppercase tracking-widest opacity-60">Live Alerts</h3>
      <button
        v-if="alerts.length"
        class="text-xs opacity-40 hover:opacity-80 transition"
        @click="dismissAll"
      >
        Clear all
      </button>
    </div>

    <transition-group
      name="alert"
      tag="ul"
      class="flex flex-col gap-2 overflow-y-auto max-h-72 pr-1"
    >
      <li
        v-for="alert in alerts"
        :key="alert.id"
        :class="alertClass(alert.severity)"
        class="flex items-start gap-3 rounded-xl border px-4 py-3 text-sm"
      >
        <span class="mt-0.5 text-base shrink-0">{{ severityIcon(alert.severity) }}</span>
        <div class="flex-1 min-w-0">
          <p class="font-medium leading-snug">{{ alert.message }}</p>
          <p class="text-xs opacity-50 mt-0.5">{{ formatTs(alert.timestamp) }}</p>
        </div>
        <button class="opacity-30 hover:opacity-80 transition shrink-0" @click="dismiss(alert.id)">
          ✕
        </button>
      </li>
    </transition-group>

    <p v-if="!alerts.length" class="text-center text-xs opacity-30 py-4">
      No alerts — system is healthy 🟢
    </p>
  </div>
</template>

<script setup>
import { useNotifications } from '@/composables/useNotifications';

const { alerts, dismiss, dismissAll } = useNotifications();

function alertClass(severity) {
  return {
    'border-red-500/40 bg-red-500/10 text-red-300':       severity === 'critical',
    'border-yellow-500/40 bg-yellow-500/10 text-yellow-300': severity === 'warning',
    'border-blue-500/40 bg-blue-500/10 text-blue-300':    severity === 'info',
  };
}

function severityIcon(severity) {
  return { critical: '🔴', warning: '🟡', info: 'ℹ️' }[severity] ?? '•';
}

function formatTs(ts) {
  if (!ts) return '';
  try {
    return new Date(ts).toLocaleTimeString();
  } catch {
    return ts;
  }
}
</script>

<style scoped>
.alert-enter-active,
.alert-leave-active {
  transition: all 0.3s ease;
}
.alert-enter-from {
  opacity: 0;
  transform: translateY(-8px);
}
.alert-leave-to {
  opacity: 0;
  transform: translateX(20px);
}
</style>
