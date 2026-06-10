<script setup>
import { RouterLink } from "vue-router";
import { Icon } from "@iconify/vue"; // Import Iconify

// Define Emits agar DashboardAdmin tau tombol ditekan
defineEmits(["toggle-menu"]);
</script>

<template>
  <header
    class="h-16 bg-white border-b-4 border-black flex items-center justify-between px-4 md:px-6 shadow-sm z-30 sticky top-0">
    <div class="flex items-center gap-3">
      <button
        @click="$emit('toggle-menu')"
        class="md:hidden p-2 border-2 border-black bg-white hover:bg-gray-100 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] active:shadow-none active:translate-x-[2px] active:translate-y-[2px] flex items-center justify-center">
        <Icon icon="lucide:menu" width="24" height="24" />
      </button>

      <h2 class="text-lg md:text-2xl font-black italic tracking-widest text-black drop-shadow-md truncate">
        DASHBOARD
      </h2>
    </div>

    <div class="flex items-center gap-3 md:gap-4">
      <div class="hidden md:flex flex-col items-end mr-2">
        <span class="font-bold text-sm">Super Admin</span>
        <span class="text-xs font-mono bg-black text-white px-1">ONLINE</span>
      </div>

      <div class="h-10 w-10 md:h-12 md:w-12 bg-gray-200 rounded-full border-2 border-black overflow-hidden shrink-0">
        <img loading="lazy" src="/me-fu.png" alt="Admin" class="object-cover object-top w-full h-full" />
      </div>

      <RouterLink
        to="/admin/dashboard/logout"
        class="bg-black hover:bg-gray-800 text-white font-bold py-1 px-3 md:px-4 text-xs md:text-sm border-2 border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] md:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all cursor-pointer whitespace-nowrap">
        LOGOUT
      </RouterLink>
    </div>
  </header>
</template>
