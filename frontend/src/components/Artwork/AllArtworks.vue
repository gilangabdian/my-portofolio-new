<script setup>
import { ref, onMounted, watch, nextTick } from 'vue';
import NProgress from 'nprogress';
import 'nprogress/nprogress.css';
import gsap from 'gsap';
import ArtworkControls from './ArtworkControls.vue';
import { getAllArtworks } from '../../lib/api/ArtworkApi';
import { useSWR } from '../../utils/useSWR';

// Hapus state lama
// const artworks = ref([]);
const isSquareGrid = ref(false);
const selectedImage = ref(null);

const { data: artworkSWR, isLoading: isSWRLoading, revalidate } = useSWR('cache_all_artworks', getAllArtworks, []);
const artworks = ref([]);
const isLoading = ref(true);

watch(isSWRLoading, async (newVal) => {
  if (!newVal) {
    artworks.value = artworkSWR.value;
    isLoading.value = false;
    NProgress.done();

    // Tunggu DOM selesai nge-render v-for
    await nextTick();

    if (artworks.value && artworks.value.length > 0) {
      gsap.fromTo(
        ".controls-container",
        { y: -10, autoAlpha: 0 },
        { y: 0, autoAlpha: 1, duration: 0.5, ease: "power2.out" }
      );
      gsap.fromTo(
        ".artwork-item, .thank-you-msg",
        { y: 20, autoAlpha: 0 },
        {
          y: 0,
          autoAlpha: 1,
          duration: 0.5,
          ease: "power2.out",
          stagger: 0.05,
          clearProps: "all"
        }
      );
    }
  }
}, { immediate: true });

const openModal = (url) => {
  selectedImage.value = url;
};

const closeModal = () => {
  selectedImage.value = null;
};

onMounted(() => {
  // SWR handles load
});
</script>

<template>
  <div class="pt-28 md:pt-36 pb-16 min-h-screen">
    <!-- Static padding wrapper for headers/controls to prevent jumping -->
    <div class="max-w-7xl mx-auto px-4 pb-4 md:px-16 lg:px-7">
    <!-- Empty State -->
    <div v-if="!isLoading && artworks.length === 0" class="text-center font-sans text-neutral-500 py-12">
      No artworks found.
    </div>

    <!-- Controls (Floating Sticky) -->
    <ArtworkControls v-if="!isLoading && artworks.length > 0" v-model="isSquareGrid" />

    </div>

    <!-- Dynamic padding wrapper specifically for the gallery -->
    <div :class="[
      'max-w-7xl mx-auto',
      isSquareGrid ? 'px-4 md:px-8 lg:px-8' : 'px-8 md:px-16 lg:px-16'
    ]">
      <!-- Gallery Grid (Row-based for vertical centering) -->
      <div v-if="!isLoading && artworks.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <div
        v-for="artwork in artworks"
        :key="artwork.id"
        :class="['artwork-item bg-white flex items-center justify-center', isSquareGrid ? 'aspect-square overflow-hidden' : '']"
        style="opacity: 0; visibility: hidden"
        @click="openModal(artwork.image_url)"
      >
        <img
          :src="artwork.image_url"
          alt="Artwork"
          loading="lazy"
          :class="['w-full', isSquareGrid ? 'h-full object-cover' : 'h-auto']"
        />
      </div>
      </div>

      <!-- Thank You Message -->
      <div v-if="!isLoading && artworks.length > 0" class="thank-you-msg mt-16 text-center text-neutral-500 font-sans text-sm tracking-wide" style="opacity: 0; visibility: hidden">
        thank you
      </div>
    </div>

    <!-- Modal -->
    <div
      v-if="selectedImage"
      class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 backdrop-blur-sm"
      @click="closeModal"
    >
      <img
        :src="selectedImage"
        class="w-full h-full object-contain"
        alt="Enlarged Artwork"
      />
    </div>
  </div>
</template>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
