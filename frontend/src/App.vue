<script setup>
import { onMounted } from "vue";
import { initConsoleFeatures } from "./utils/consoleManager";
import { useHead } from "@unhead/vue";

useHead({
  titleTemplate: (title) => title ? `${title} | Gilang Abdian` : 'Gilang Abdian',
});

onMounted(() => {
  initConsoleFeatures();
});
</script>

<template>
  <RouterView />
</template>

<style scoped></style>
