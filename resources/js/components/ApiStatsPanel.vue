<template>
  <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
    <h3 class="text-sm font-semibold uppercase tracking-widest opacity-60 mb-4">
      Third-Party API Stats
    </h3>

    <!-- GitHub rate limit -->
    <div v-if="github && !github.error" class="mb-4">
      <p class="text-xs opacity-50 mb-1">GitHub API Rate Limit</p>
      <div class="flex items-center gap-3">
        <div class="flex-1 h-2 rounded-full bg-white/10">
          <div
            class="h-full rounded-full bg-emerald-400 transition-all duration-700"
            :style="{ width: githubUsedPercent + '%' }"
          />
        </div>
        <span class="text-xs tabular-nums opacity-70">
          {{ github.used }} / {{ github.limit }}
        </span>
      </div>
      <p class="text-xs opacity-40 mt-1">Resets {{ githubResetTime }}</p>
    </div>
    <p v-else-if="github?.error" class="text-xs opacity-40">GitHub: unavailable</p>

    <!-- Weather -->
    <div v-if="weather && !weather.error && !weather.note">
      <p class="text-xs opacity-50 mb-1">Weather — {{ weather.city }}</p>
      <div class="flex items-center gap-4 text-sm">
        <span>🌡 {{ weather.temperature }}°C</span>
        <span>💧 {{ weather.humidity }}%</span>
        <span class="opacity-60 capitalize">{{ weather.description }}</span>
      </div>
    </div>
    <p v-else-if="weather?.note" class="text-xs opacity-40">{{ weather.note }}</p>
    <p v-else-if="weather?.error" class="text-xs opacity-40">Weather: unavailable</p>

    <p v-if="!github && !weather" class="text-xs opacity-30 text-center py-2">
      Fetching API stats…
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  apiStats: { type: Object, default: null },
});

const github  = computed(() => props.apiStats?.github  ?? null);
const weather = computed(() => props.apiStats?.weather ?? null);

const githubUsedPercent = computed(() => {
  if (!github.value?.limit) return 0;
  return Math.round((github.value.used / github.value.limit) * 100);
});

const githubResetTime = computed(() => {
  if (!github.value?.reset) return '';
  try {
    return new Date(github.value.reset * 1000).toLocaleTimeString();
  } catch {
    return '';
  }
});
</script>
