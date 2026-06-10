<script setup lang="ts">
import Footer from "../Footer.vue";
import Navbar from "../Navbar.vue";
import ScrollToTop from "../ScrollToTop.vue";
</script>

<template>
  <Navbar />
  <RouterView />
  <Footer />
  <ScrollToTop />
</template>
