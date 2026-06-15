<script setup>
import { ref } from "vue";
import SidebarAdmin from "./Section/SidebarAdmin.vue";
import NavbarAdmin from "./Section/NavbarAdmin.vue";
import FooterAdmin from "./Section/FooterAdmin.vue";

// State untuk Mobile Sidebar
const isMobileSidebarOpen = ref(false);

const toggleMobileSidebar = () => {
  isMobileSidebarOpen.value = !isMobileSidebarOpen.value;
};

const closeMobileSidebar = () => {
  isMobileSidebarOpen.value = false;
};
</script>

<template>
  <div class="flex h-[100dvh] w-screen overflow-hidden bg-gray-50 text-black font-sans relative">
    <div
      v-if="isMobileSidebarOpen"
      @click="closeMobileSidebar"
      class="fixed inset-0 bg-black/50 z-40 md:hidden backdrop-blur-sm transition-opacity"></div>

    <SidebarAdmin :isMobileOpen="isMobileSidebarOpen" @close-mobile="closeMobileSidebar" />

    <div class="flex flex-col flex-1 min-w-0 overflow-hidden relative">
      <NavbarAdmin @toggle-menu="toggleMobileSidebar" />

      <main class="flex-1 overflow-y-auto p-4 md:p-6 bg-gray-50">
        <RouterView />
      </main>

      <FooterAdmin />
    </div>
  </div>
</template>
