<script setup>
import { ref } from "vue";

const count = ref(0);
</script>

<template>
<main class="bg-[#121212] h-[100vh]">
  <div id="main" class="flex flex-col items-center justify-center pt-20 text-[#f3f4f6]">
    <h1>hi, i'm Abdian. i'm a web enthusiast</h1>
    <div class="bg-blue pt-8">
      <RouterLink to="/me" class="bg-[#1e1e1e] px-8 rounded-md hover:border-white/20 border border-transparent hover:border-white/20 text-[#f3f4f6] p-2">next</RouterLink>
    </div>
  </div>
  </main>
</template>

<style scoped>


</style>
