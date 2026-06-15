<script setup>
import { onMounted } from "vue";
import { initConsoleFeatures } from "./utils/consoleManager";

onMounted(() => {
  initConsoleFeatures();
});
</script>

<template>
  <RouterView />
</template>

<style scoped></style>
