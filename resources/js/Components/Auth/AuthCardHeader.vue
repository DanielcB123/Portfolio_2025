<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    required: true,
  },
  badge: {
    type: String,
    required: true,
  },
  tagline: {
    type: String,
    required: true,
  },
  showPortfolioLabel: {
    type: Boolean,
    default: true,
  },
})
</script>

<template>
  <div class="mb-6 flex items-center justify-between">
    <div>
      <p
        v-if="showPortfolioLabel"
        class="text-[11px] uppercase tracking-[0.2em] text-emerald-400"
      >
        Portfolio demo
      </p>
      <h1
        class="text-base font-semibold text-slate-900 dark:text-slate-100"
        :class="{ 'mt-1': showPortfolioLabel }"
      >
        {{ title }}
      </h1>
      <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">
        {{ subtitle }}
      </p>
    </div>
    <div
      class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] text-slate-700 dark:border-slate-700/70 dark:bg-slate-900/80 dark:text-slate-300"
    >
      <span
        class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-emerald-400 text-[10px] font-semibold text-slate-950"
      >
        {{ badge }}
      </span>
      <span class="hidden sm:inline">
        {{ tagline }}
      </span>
    </div>
  </div>
</template>
