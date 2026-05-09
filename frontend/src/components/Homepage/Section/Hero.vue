<script setup>
import { onMounted, ref, watch, computed } from "vue";
import { Icon } from "@iconify/vue";
import gsap from "gsap";

const props = defineProps({
  profile: {
    type: Object,
    required: true,
    default: () => ({}),
  },
});

// State untuk efek mengetik
const displayedJob = ref("");
const cursorVisible = ref(true);

// Mengambil teks Job Title dengan aman
const jobTitleText = computed(() => props.profile.about?.job_title || "");

// Fungsi efek mengetik (Typewriter)
const startTypewriter = () => {
  const text = jobTitleText.value;
  if (!text) return;

  let i = 0;
  displayedJob.value = "";

  // Interval mengetik (Speed: 100ms per huruf)
  // Kita pakai setTimeout recursively biar bisa diatur variasi kecepatannya kalau mau lebih "human"
  const typeChar = () => {
    if (i < text.length) {
      displayedJob.value += text.charAt(i);
      i++;
      setTimeout(typeChar, 100); // Kecepatan mengetik
    }
  };

  typeChar();
};

onMounted(() => {
  // 1. Animasi Kursor Berkedip (Blinking)
  // Ini jalan terus menerus
  setInterval(() => {
    cursorVisible.value = !cursorVisible.value;
  }, 500);

  // 2. Timeline Utama GSAP untuk Elemen Hero
  const tl = gsap.timeline();

  // A. Badge "Available" muncul Pop-up
  tl.from(".hero-badge", {
    scale: 0,
    rotation: -20,
    opacity: 0,
    duration: 0.6,
    ease: "back.out(1.7)",
  })
    // B. "Hi I'm" dan Nama muncul Fade In + Geser dikit
    .from(
      ".hero-text",
      {
        y: 20,
        opacity: 0,
        duration: 0.8,
        stagger: 0.2,
        ease: "power2.out",
      },
      "-=0.4",
    )
    // C. Deskripsi & Tombol muncul
    .from(
      ".hero-content",
      {
        y: 20,
        opacity: 0,
        duration: 0.8,
        stagger: 0.1,
        ease: "power2.out",
      },
      "-=0.6",
    )
    // D. Foto Profil muncul dari bawah (seperti Polaroid)
    .from(
      ".hero-image",
      {
        y: 100,
        opacity: 0,
        scale: 0.9,
        duration: 1.2,
        ease: "elastic.out(1, 0.75)",
      },
      "-=1.0",
    ); // Jalan barengan sama animasi sebelumnya

  // 3. Jalankan Typewriter setelah animasi pembuka mulai
  // Kita beri delay sedikit biar ga tabrakan sama animasi nama
  setTimeout(() => {
    startTypewriter();
  }, 1000);
});

// Watcher: Jaga-jaga kalau data profile telat load dari API
watch(
  () => props.profile.about,
  (newVal) => {
    if (newVal && displayedJob.value === "") {
      setTimeout(() => startTypewriter(), 1000);
    }
  },
);
</script>

<template>
  <div v-if="props.profile && props.profile.about"
    class="container mx-auto px-6 pt-26 pb-6 md:pt-8 md:pb-0 min-h-screen flex items-center justify-center overflow-hidden">
    <div class="flex flex-col-reverse md:flex-row items-center justify-between w-full max-w-4xl gap-8 md:gap-2 mt-8">
      <div class="flex-1 flex flex-col items-start space-y-3 md:space-y-3 mt-4">
        <div
          class="hero-badge inline-block bg-[#f8f8f8] dark:bg-white/5 border border-black/20 dark:border-white/10 px-3 py-1 rounded-lg shadow-sm transform -rotate-1 origin-bottom-left">
          <h5 class="font-bold text-[10px] md:text-xs tracking-wide uppercase">Available for Work</h5>
        </div>

        <div class="space-y-0.5">
          <h1 class="hero-text text-4xl md:text-5xl font-bold leading-tight font-[Inter] tracking-tight">
            Hi, I'm
            <br class="hidden md:block" />
            <span class="underline decoration-4 underline-offset-4 decoration-black dark:decoration-white">{{
              profile.about.name }}</span>
          </h1>

          <h2
            class="hero-text text-xl md:text-2xl lg:text-3xl font-[Playfair_Display] italic text-gray-800 pt-1 min-h-[1.5em] flex items-center">
            <span>{{ displayedJob }}</span>
            <span class="inline-block w-[2px] h-[24px] md:h-[32px] bg-black dark:bg-white ml-1 align-middle"
              :class="{ 'opacity-0': !cursorVisible, 'opacity-100': cursorVisible }"></span>
          </h2>
        </div>

        <p class="hero-content text-sm text-justify leading-relaxed max-w-lg font-[Inter] text-gray-700">
          {{ profile.about.about_description }}
        </p>

        <div class="hero-content flex flex-wrap gap-2 font-medium text-xs">
          <div class="flex items-center gap-1.5 py-1">
            <Icon icon="mdi:map-marker" />
            <span>Based in Indonesia</span>
          </div>
          <div class="flex items-center gap-1.5 px-2.5 py-1 text-black dark:text-white">
            <div class="w-2 h-2 bg-black dark:bg-white rounded-full animate-pulse"></div>
            <span>Available Now</span>
          </div>
        </div>

        <div class="hero-content flex gap-3 pt-1 w-full md:w-auto">
          <a href="mailto:qbdian@gmail.com?subject=Hi Gilang Abdian Anggara, I want to hire you!"
            class="flex-1 md:flex-none flex items-center justify-center gap-2 bg-black text-white dark:bg-white dark:text-black px-0 md:px-5 py-2 rounded-xl border border-transparent font-bold text-sm shadow-[0_4px_14px_0_rgba(0,0,0,0.39)] hover:shadow-[0_6px_20px_rgba(0,0,0,0.23)] hover:bg-black/90 dark:hover:bg-gray-200 active:scale-95 transition-all">
            <Icon icon="mdi:handshake-outline" class="w-4 h-4 md:w-5 md:h-5" />
            <span>Hire Me</span>
          </a>

          <a :href="profile.about.cv_url" target="_blank"
            class="flex-1 md:flex-none flex items-center justify-center gap-2 bg-white text-black px-0 md:px-5 py-2 rounded-xl border border-black/20 font-bold text-sm shadow-[0_4px_14px_0_rgba(0,0,0,0.1)] hover:shadow-[0_6px_20px_rgba(0,0,0,0.15)] hover:bg-gray-50 active:scale-95 transition-all">
            <Icon icon="mdi:file-download-outline" class="w-4 h-4 md:w-5 md:h-5" />
            <span>Download CV</span>
          </a>
        </div>

        <hr class="hero-content w-full border-t border-black/20" />

        <div class="hero-content flex flex-col md:flex-row items-start md:items-center gap-2 text-xs font-bold">
          <span class="whitespace-nowrap">Follow me:</span>
          <div class="flex flex-wrap gap-2">
            <a v-for="social in profile.social_media" :key="social.name" :href="social.url" target="_blank"
              class="p-1.5 border border-black/20 rounded-lg hover:bg-gray-50 hover:shadow-sm transition-all duration-200"
              :title="social.name">
              <Icon :icon="social.icon" class="w-4 h-4 text-black" />
            </a>
          </div>
        </div>
      </div>

      <div class="w-full md:w-5/12 flex justify-center md:justify-end relative hero-image">
        <div class="absolute inset-0 bg-gray-100 rounded-full scale-90 blur-3xl -z-10 opacity-50"></div>

        <img :src="profile.about.photo_url" alt="Gilang Abdian"
          class="w-[400px] md:w-[300px] -mt-32 md:mt-0 h-auto object-cover grayscale contrast-110 border-b border-black/20" />
      </div>
    </div>
  </div>
</template>