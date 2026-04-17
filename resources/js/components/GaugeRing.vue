<template>
  <div class="flex flex-col items-center">
    <svg :width="size" :height="size" class="-rotate-90" viewBox="0 0 100 100" aria-hidden="true">
      <!-- track -->
      <circle cx="50" cy="50" :r="radius" fill="none" stroke="rgba(255,255,255,0.08)"
        :stroke-width="strokeWidth" />
      <!-- fill -->
      <circle cx="50" cy="50" :r="radius" fill="none" :stroke="color"
        :stroke-width="strokeWidth"
        stroke-linecap="round"
        :stroke-dasharray="circumference"
        :stroke-dashoffset="dashOffset"
        class="transition-all duration-700" />
    </svg>
    <div class="mt-2 text-center">
      <span class="text-2xl font-bold tabular-nums">{{ value }}%</span>
      <p class="text-xs opacity-50">{{ label }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  value:       { type: Number, default: 0 },
  label:       { type: String, default: '' },
  size:        { type: Number, default: 120 },
  strokeWidth: { type: Number, default: 10 },
  color:       { type: String, default: '#818cf8' },
});

const radius      = computed(() => 50 - props.strokeWidth / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const dashOffset  = computed(() => circumference.value * (1 - Math.min(props.value, 100) / 100));
</script>
