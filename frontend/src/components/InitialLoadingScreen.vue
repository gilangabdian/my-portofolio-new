<script setup>
import { computed } from "vue";
import { useTransition, TransitionPresets } from "@vueuse/core";

const props = defineProps({
  percent: {
    type: Number,
    default: 0,
  },
});

const sourcePercent = computed(() => Math.min(100, Math.max(0, props.percent)));

const smoothPercent = useTransition(sourcePercent, {
  duration: 800,
  transition: TransitionPresets.easeOutCubic,
});

const displayPercent = computed(() => Math.round(smoothPercent.value));

// Simplified message as requested
const statusText = computed(() => {
  return "Curating the best experience for you...";
});
</script>

<template>
  <div class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-black transition-colors duration-700">
    <div class="flex flex-col items-center gap-8 max-w-xs w-full px-6">
      <!-- Minimalist Progress Tracker -->
      <div class="relative w-full">
        <!-- Percentage indicator -->
        <div class="flex justify-between items-end mb-2">
          <span class="text-white/40 text-xs font-mono tracking-widest uppercase">Initializing</span>
          <span class="text-white text-4xl font-light font-sans tracking-tighter">
            {{ displayPercent }}<span class="text-sm opacity-50 ml-0.5">%</span>
          </span>
        </div>

        <!-- Progress Bar (Thin & Elegant) -->
        <div class="w-full h-[2px] bg-[#ffffff]/10 overflow-hidden rounded-full">
          <div
            class="h-full bg-[#ffffff] shadow-[0_0_8px_rgba(255,255,255,0.5)]"
            :style="{ width: smoothPercent + '%' }"></div>
        </div>
      </div>

      <!-- Subtle Status Message -->
      <p class="text-white/60 text-[10px] md:text-xs font-medium tracking-[0.2em] uppercase text-center animate-pulse">
        {{ statusText }}
      </p>
    </div>
    
    <!-- Decorative subtle glow in corners for premium feel -->
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-[#ffffff]/5 rounded-full blur-[120px] -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
    <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-[#ffffff]/5 rounded-full blur-[120px] translate-y-1/2 -translate-x-1/2 pointer-events-none"></div>
  </div>
</template>

<style scoped>
/* Smooth fade for the percentage text changes */
.font-sans {
  font-family: 'Inter', 'Outfit', sans-serif;
}
</style>