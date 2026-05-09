<script setup>
import { onMounted, nextTick } from "vue";
import { Icon } from "@iconify/vue";
import gsap from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";

gsap.registerPlugin(ScrollTrigger);

const props = defineProps({
  skills: {
    type: Array,
    required: true,
    default: () => [],
  },
});

// Rotasi acak
const getRotationClass = (index) => {
  const rotations = ["rotate-2", "-rotate-1", "rotate-3", "-rotate-2", "rotate-1", "-rotate-3", "rotate-0"];
  return rotations[index % rotations.length];
};

// Posisi vertikal acak
const getTranslateClass = (index) => {
  const translates = ["translate-y-0", "translate-y-2", "translate-y-0", "-translate-y-2"];
  return translates[index % translates.length];
};

onMounted(async () => {
  await nextTick();

  // 1. SET INITIAL STATE (Sembunyikan dulu semua sebelum animasi)
  // Header
  gsap.set(".header-animate", { y: 50, opacity: 0 });
  // Kartu
  gsap.set(".polaroid-card", { y: 100, opacity: 0, scale: 0.8 });

  // 2. ANIMASI HEADER (Judul & Deskripsi)
  gsap.to(".header-animate", {
    scrollTrigger: {
      trigger: ".header-section",
      start: "top 80%", // Mulai saat header masuk 80% viewport
      once: true, // Animasi hanya berjalan 1 kali
    },
    y: 0,
    opacity: 1,
    duration: 1,
    stagger: 0.2, // Jeda antara H2 dan P
    ease: "power3.out",
  });

  // 3. ANIMASI KARTU (BATCH)
  // Ini solusinya: 'batch' akan mendeteksi elemen mana saja yang masuk layar
  ScrollTrigger.batch(".polaroid-card", {
    start: "top 85%", // Mulai saat bagian atas KARTU (bukan container) masuk 85% layar
    once: true, // Animasi batch hanya berjalan 1 kali
    onEnter: (batch) => {
      // Animasi hanya untuk kartu yang sedang dilihat (batch)
      gsap.to(batch, {
        opacity: 1,
        y: 0,
        scale: 1,
        duration: 1.5,
        ease: "elastic.out(1, 0.75)",
        stagger: 0.2, // Jeda antar kartu dalam satu baris
        overwrite: true,
      });
    },
  });
});
</script>

<template>
  <section class="py-24 px-4 md:px-10 bg-white overflow-hidden min-h-screen">
    <div class="max-w-6xl mx-auto">
      <div class="header-section text-center mb-20 max-w-3xl mx-auto">
        <h2
          class="header-animate text-4xl font-black text-black mb-6 font-serif tracking-wider inline-block relative border-b border-black/20 pb-2">
          <span class="relative z-10">Tech Stack</span>
        </h2>

        <p
          class="header-animate mt-4 font-sans text-gray-500 text-sm md:text-base lowercase tracking-tight leading-relaxed">
          here are the tools and technologies I have worked with to build my projects. My stack evolves as I learn, but
          every tool has played a role in my journey. I am mostly self-taught and still learning every day—treating
          every line of code as an opportunity to discover something new.
        </p>
      </div>

      <div class="flex flex-wrap justify-center gap-x-4 gap-y-12 md:gap-x-8 md:gap-y-16 p-4">
        <div v-for="(skill, index) in props.skills" :key="skill.id"
          class="polaroid-card relative group w-[45%] md:w-[28%] lg:w-[16%] max-w-[180px]"
          :class="[getRotationClass(index), getTranslateClass(index)]">
          <div
            class="absolute -top-3 left-1/2 -translate-x-1/2 w-4 h-4 bg-black rounded-full z-20 shadow-sm border border-gray-600">
          </div>

          <div
            class="bg-white p-3 pb-8 border border-black/20 shadow-sm transition-all duration-300 hover:scale-105 hover:z-10 hover:shadow-md hover:rotate-0 cursor-default rounded-sm">
            <div
              class="aspect-square bg-gray-50 border border-black/10 mb-4 flex items-center justify-center relative overflow-hidden group-hover:bg-white transition-colors rounded-sm">
              <Icon :icon="skill.identifier" class="w-12 h-12 md:w-14 md:h-14 text-black relative z-10" />
            </div>

            <div class="text-center">
              <span
                class="font-serif font-bold text-black text-sm md:text-base uppercase tracking-widest border-b border-transparent group-hover:border-black/20 transition-all pb-1">
                {{ skill.name }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Penting: opacity awal diatur lewat GSAP set, tapi style ini menjaga performa render */
.header-animate,
.polaroid-card {
  will-change: transform, opacity;
}
</style>