<template>
  <div
    class="rounded-2xl border p-5 transition-colors duration-500"
    :class="colorClass"
  >
    <div class="flex items-start justify-between">
      <div>
        <p class="text-xs font-medium uppercase tracking-widest opacity-60">{{ label }}</p>
        <p class="mt-1 text-3xl font-bold tabular-nums">{{ displayValue }}</p>
        <p v-if="subtitle" class="mt-1 text-xs opacity-50">{{ subtitle }}</p>
      </div>
      <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-xl">
        <slot name="icon" />
      </div>
    </div>

    <!-- progress bar -->
    <div class="mt-4 h-1.5 w-full rounded-full bg-white/10">
      <div
        class="h-full rounded-full transition-all duration-700"
        :class="barColor"
        :style="{ width: barWidth }"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label:    { type: String, required: true },
  value:    { type: Number, default: 0 },
  unit:     { type: String, default: '%' },
  subtitle: { type: String, default: '' },
  severity: { type: String, default: 'normal' }, // normal | warning | critical
});

const displayValue = computed(() => `${props.value}${props.unit}`);

const barWidth = computed(() => {
  const p = props.unit === '%' ? props.value : 0;
  return `${Math.min(Math.max(p, 0), 100)}%`;
});

const colorClass = computed(() => ({
  'border-yellow-500/40 bg-yellow-500/5':  props.severity === 'warning',
  'border-red-500/40 bg-red-500/5':        props.severity === 'critical',
  'border-white/10 bg-white/5':            props.severity === 'normal',
}));

const barColor = computed(() => ({
  'bg-yellow-400': props.severity === 'warning',
  'bg-red-500':    props.severity === 'critical',
  'bg-indigo-400': props.severity === 'normal',
}));
</script>
