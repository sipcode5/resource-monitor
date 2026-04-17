<template>
  <div class="relative w-full overflow-hidden" :style="{ height: `${height}px` }">
    <svg
      v-if="points.length > 1"
      class="absolute inset-0 w-full h-full"
      preserveAspectRatio="none"
      :viewBox="`0 0 ${vbWidth} ${height}`"
    >
      <!-- gradient fill -->
      <defs>
        <linearGradient :id="gradId" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" :stop-color="color" stop-opacity="0.4" />
          <stop offset="100%" :stop-color="color" stop-opacity="0" />
        </linearGradient>
      </defs>
      <path :d="fillPath" :fill="`url(#${gradId})`" />
      <polyline
        :points="svgPoints"
        fill="none"
        :stroke="color"
        stroke-width="1.5"
        stroke-linejoin="round"
        stroke-linecap="round"
      />
    </svg>
    <div v-else class="flex h-full items-center justify-center text-xs opacity-30">
      Collecting…
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  data:   { type: Array, default: () => [] }, // array of numbers 0-100
  height: { type: Number, default: 48 },
  color:  { type: String, default: '#818cf8' },
});

const gradId  = `grad-${Math.random().toString(36).slice(2)}`;
const vbWidth = 200;

const points = computed(() => props.data.filter((v) => v !== null && v !== undefined));

const svgPoints = computed(() => {
  if (points.value.length < 2) return '';
  const step = vbWidth / (points.value.length - 1);
  return points.value
    .map((v, i) => `${i * step},${props.height - (v / 100) * props.height}`)
    .join(' ');
});

const fillPath = computed(() => {
  if (points.value.length < 2) return '';
  const step = vbWidth / (points.value.length - 1);
  const pts  = points.value.map((v, i) => `${i * step},${props.height - (v / 100) * props.height}`);
  return `M${pts.join('L')}L${vbWidth},${props.height}L0,${props.height}Z`;
});
</script>
