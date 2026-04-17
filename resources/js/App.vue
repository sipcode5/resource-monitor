<template>
  <div class="min-h-screen bg-gray-950 text-gray-100 font-sans">

    <!-- ── Top-bar ─────────────────────────────────────────────────────── -->
    <header class="sticky top-0 z-10 border-b border-white/10 bg-gray-950/80 backdrop-blur">
      <div class="mx-auto max-w-7xl flex items-center justify-between px-6 py-4">
        <div class="flex items-center gap-3">
          <span class="text-2xl">📡</span>
          <div>
            <h1 class="text-base font-bold leading-none">Resource Monitor</h1>
            <p class="text-xs opacity-40 mt-0.5">Real-Time System Dashboard</p>
          </div>
        </div>
        <div class="flex items-center gap-4">
          <!-- Connection badge -->
          <div class="flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs"
            :class="connected
              ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-400'
              : 'border-yellow-500/40 bg-yellow-500/10 text-yellow-400'"
          >
            <span class="inline-block h-2 w-2 rounded-full"
              :class="connected ? 'bg-emerald-400 animate-pulse' : 'bg-yellow-400'"
            />
            {{ connected ? 'Live' : 'Polling' }}
          </div>

          <span v-if="metrics?.timestamp" class="text-xs opacity-30 hidden sm:block">
            Updated {{ formatTime(metrics.timestamp) }}
          </span>

          <button
            class="rounded-xl border border-white/10 bg-white/5 px-4 py-1.5 text-sm hover:bg-white/10 transition"
            :disabled="loading"
            @click="refresh"
          >
            {{ loading ? 'Refreshing…' : '↻ Refresh' }}
          </button>
        </div>
      </div>
    </header>

    <!-- ── Main ──────────────────────────────────────────────────────────── -->
    <main class="mx-auto max-w-7xl px-6 py-8 space-y-8">

      <!-- Loading skeleton -->
      <div v-if="loading && !metrics" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div v-for="i in 3" :key="i"
          class="h-36 rounded-2xl border border-white/10 bg-white/5 animate-pulse"
        />
      </div>

      <template v-else-if="metrics">

        <!-- ── Gauges Row ────────────────────────────────────────────── -->
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <div class="flex flex-col items-center gap-2">
            <GaugeRing
              :value="metrics.cpu_percent ?? 0"
              label="CPU"
              :color="gaugeColor(metrics.cpu_percent ?? 0)"
            />
            <SparkLine :data="history.map(h => h.cpu)" color="#818cf8" class="w-full" />
          </div>

          <div class="flex flex-col items-center gap-2">
            <GaugeRing
              :value="metrics.memory?.used_percent ?? 0"
              label="Memory"
              :color="gaugeColor(metrics.memory?.used_percent ?? 0)"
            />
            <SparkLine :data="history.map(h => h.mem)" color="#34d399" class="w-full" />
          </div>

          <div class="flex flex-col items-center gap-2">
            <GaugeRing
              :value="metrics.disk?.used_percent ?? 0"
              label="Disk"
              :color="gaugeColor(metrics.disk?.used_percent ?? 0)"
            />
            <SparkLine :data="history.map(h => h.disk)" color="#fb923c" class="w-full" />
          </div>
        </section>

        <!-- ── Metric Cards ──────────────────────────────────────────── -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <MetricCard
            label="CPU Usage"
            :value="metrics.cpu_percent ?? 0"
            unit="%"
            :subtitle="`Load avg: ${(metrics.load_average?.[0] ?? 0).toFixed(2)}`"
            :severity="thresholdSeverity(metrics.cpu_percent ?? 0)"
          >
            <template #icon>⚙️</template>
          </MetricCard>

          <MetricCard
            label="Memory Used"
            :value="metrics.memory?.used_percent ?? 0"
            unit="%"
            :subtitle="formatBytes(metrics.memory?.used_bytes) + ' / ' + formatBytes(metrics.memory?.total_bytes)"
            :severity="thresholdSeverity(metrics.memory?.used_percent ?? 0)"
          >
            <template #icon>🧠</template>
          </MetricCard>

          <MetricCard
            label="Disk Used"
            :value="metrics.disk?.used_percent ?? 0"
            unit="%"
            :subtitle="formatBytes(metrics.disk?.used_bytes) + ' / ' + formatBytes(metrics.disk?.total_bytes)"
            :severity="thresholdSeverity(metrics.disk?.used_percent ?? 0, 80, 90)"
          >
            <template #icon>💾</template>
          </MetricCard>

          <MetricCard
            label="Uptime"
            :value="Math.floor((metrics.uptime_seconds ?? 0) / 3600)"
            unit="h"
            :subtitle="formatUptime(metrics.uptime_seconds ?? 0)"
            severity="normal"
          >
            <template #icon>⏱</template>
          </MetricCard>
        </section>

        <!-- ── Bottom Row ────────────────────────────────────────────── -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

          <!-- Notifications (spans 2/3) -->
          <div class="lg:col-span-2 rounded-2xl border border-white/10 bg-white/5 p-5">
            <NotificationFeed />
          </div>

          <!-- API stats panel -->
          <ApiStatsPanel :api-stats="metrics.api_stats ?? null" />

        </section>

        <!-- ── Raw JSON debug (dev only) ────────────────────────────── -->
        <details v-if="isDev" class="rounded-xl border border-white/10 bg-white/5 p-4">
          <summary class="text-xs opacity-40 cursor-pointer">Raw payload</summary>
          <pre class="mt-2 text-xs opacity-60 overflow-auto">{{ JSON.stringify(metrics, null, 2) }}</pre>
        </details>

      </template>

      <!-- Error state -->
      <div v-else-if="error" class="text-center py-20 opacity-50">
        <p class="text-4xl mb-4">⚠️</p>
        <p>{{ error }}</p>
      </div>

    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useMetrics }        from '@/composables/useMetrics';
import MetricCard            from '@/components/MetricCard.vue';
import GaugeRing             from '@/components/GaugeRing.vue';
import SparkLine             from '@/components/SparkLine.vue';
import NotificationFeed      from '@/components/NotificationFeed.vue';
import ApiStatsPanel         from '@/components/ApiStatsPanel.vue';

const { metrics, connected, loading, error, history, refresh } = useMetrics();

const isDev = import.meta.env.DEV;

// ── Helpers ──────────────────────────────────────────────────────────────
function gaugeColor(pct) {
  if (pct >= 90) return '#f87171'; // red-400
  if (pct >= 75) return '#fbbf24'; // amber-400
  return '#818cf8';                // indigo-400
}

function thresholdSeverity(pct, warnAt = 75, critAt = 90) {
  if (pct >= critAt)  return 'critical';
  if (pct >= warnAt)  return 'warning';
  return 'normal';
}

function formatBytes(bytes) {
  if (!bytes) return '0 B';
  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  let i = 0;
  let v = bytes;
  while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
  return `${v.toFixed(1)} ${units[i]}`;
}

function formatUptime(seconds) {
  const d = Math.floor(seconds / 86400);
  const h = Math.floor((seconds % 86400) / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  if (d > 0) return `${d}d ${h}h ${m}m`;
  if (h > 0) return `${h}h ${m}m`;
  return `${m}m`;
}

function formatTime(ts) {
  try { return new Date(ts).toLocaleTimeString(); }
  catch { return ts; }
}
</script>
