<script setup>
// Secondary loading screen for navigation (uses infinite animation)
</script>

<template>
  <div class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-black transition-colors duration-700">
    <div class="flex flex-col items-center gap-6 max-w-xs w-full px-6">
      
      <!-- Percentage/Status indicators -->
      <div class="relative w-full">
        <div class="flex justify-between items-end mb-2">
          <span class="text-white/40 text-[10px] font-mono tracking-widest uppercase">Transitioning</span>
          <span class="text-white/60 text-xs font-mono animate-pulse">...</span>
        </div>

        <!-- Progress Bar (Infinite Loop style for secondary loads) -->
        <div class="w-full h-[1px] bg-[#ffffff]/10 overflow-hidden rounded-full">
          <div class="h-full bg-[#ffffff] animate-loading-bar-infinite shadow-[0_0_8px_rgba(255,255,255,0.4)]"></div>
        </div>
      </div>

    </div>
    
    <!-- Decorative subtle glow -->
    <div class="absolute top-0 right-0 w-[400px] h-[400px] bg-[#ffffff]/5 rounded-full blur-[100px] -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
    <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-[#ffffff]/5 rounded-full blur-[100px] translate-y-1/2 -translate-x-1/2 pointer-events-none"></div>
  </div>
</template>

<style scoped>
.animate-loading-bar-infinite {
  width: 40%;
  animation: loading-infinite 1.5s infinite ease-in-out;
}

@keyframes loading-infinite {
  0% { transform: translateX(-150%); }
  50% { transform: translateX(100%); }
  100% { transform: translateX(250%); }
}

.font-mono {
  font-family: 'JetBrains Mono', 'Fira Code', monospace;
}
</style>
