<script setup>
const scrollToTop = () => {
  window.scrollTo({
    top: 0,
    behavior: "smooth"
  });
};
</script>

<template>
  <footer class="bg-white text-black relative pt-16 pb-30 md:pb-8 px-8 flex flex-col items-center justify-end z-50">

    <!-- Minimalist Text Content (Menempel di layar bawah, tanpa efek hover) -->
    <div class="text-sm font-sans text-gray-500">
      &copy; 2026 Gilang Abdian
    </div>

    <!-- Scroll to Top Button (Lebih kecil, pojok kanan bawah, hover agak gelap saja) -->
    <button @click="scrollToTop"
      class="cursor-pointer absolute bottom-28 md:bottom-6 right-6 md:right-8 bg-white text-gray-500 p-2 md:p-2.5 rounded-full border border-gray-200 shadow-sm hover:bg-gray-100 hover:text-gray-800 hover:shadow-md transition-all duration-300 flex items-center justify-center group/btn"
      aria-label="Scroll to top">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 group-hover/btn:-translate-y-0.5 transition-transform"
        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" />
      </svg>
    </button>
  </footer>
</template>

<style scoped>
footer {
  font-family: 'sans', system-ui, -apple-system, sans-serif;
}
</style>