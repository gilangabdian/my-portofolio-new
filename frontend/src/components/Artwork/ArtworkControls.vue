<script setup>
import { Icon } from '@iconify/vue';

defineProps({
  modelValue: {
    type: Boolean,
    required: true
  }
});

defineEmits(['update:modelValue']);
</script>

<template>
  <div class="fixed top-20 md:top-24 z-40 flex justify-start mb-6 controls-container" style="opacity: 0; visibility: hidden">
    <button
      @click="$emit('update:modelValue', !modelValue)"
      class="flex items-center justify-center p-2 text-xl bg-transparent hover:bg-black/5 dark:hover:bg-white/10 rounded-md cursor-pointer focus:outline-none transition-colors"
      title="Switch View"
    >
      <Icon
        :icon="modelValue ? 'mdi:grid' : 'ri:layout-masonry-line'"
        class="text-gray-500 hover:text-black dark:text-gray-400 dark:hover:text-white transition-colors"
      />
    </button>
  </div>
</template>
