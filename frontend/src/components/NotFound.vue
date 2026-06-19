<script setup>
import { onMounted } from "vue";
import { RouterLink } from "vue-router";
import { useDark } from "@vueuse/core";
import NProgress from "nprogress";

// Initialize theme logic for this page
const isDark = useDark({ initialValue: "dark" });

onMounted(() => {
  NProgress.done(); // Close the router's progress bar — this page has no data fetch
});
</script>

<template>
  <div
    class="min-h-screen flex flex-col items-center justify-center transition-colors duration-300 px-6 text-center font-sans">
    <!-- 404 Kecil -->
    <h1 class="text-xl md:text-2xl font-bold text-black dark:text-white mb-2 tracking-widest">
      404
    </h1>

    <!-- Deskripsi -->
    <p class="text-gray-700 text-sm md:text-base mb-10 lowercase tracking-tight">
      sorry, the page is not available
    </p>

    <!-- Link Balik Home (Sangat Minimalis) -->
    <router-link to="/"
      class="text-[10px] uppercase tracking-[0.2em] text-black dark:text-white opacity-50 hover:opacity-100 hover:underline decoration-1 underline-offset-8 transition-all duration-300">
      Back to Home
    </router-link>
  </div>
</template>
