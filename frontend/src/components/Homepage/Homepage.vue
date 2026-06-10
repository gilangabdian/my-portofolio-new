<script setup>
import { ref, onMounted, watch, nextTick, onUnmounted } from "vue";
import NProgress from "nprogress";
// --- 1. Import GSAP dan ScrollTrigger ---
import gsap from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";

import { getProfile } from "../../lib/api/ProfileApi";
import { getAllProjects } from "../../lib/api/ProjectApi";
import { getSkills } from "../../lib/api/SkillApi";
import { getAllCertificates } from "../../lib/api/CertificateApi";
import { getAllExperiences } from "../../lib/api/ExperienceApi";
import { logVisitor } from "../../lib/api/VisitorApi";

import Experience from "./Section/Experience.vue";
import FeaturedCertificate from "./Section/FeaturedCertificate.vue";
import FeaturedProject from "./Section/FeaturedProject.vue";
// import Philosophy from "./Section/Philosophy.vue";
import Hero from "./Section/Hero.vue";
import Tech from "./Section/Tech.vue";
import InitialLoadingScreen from "../InitialLoadingScreen.vue";
import { useSWR } from "../../utils/useSWR";

// Register GSAP Plugin
gsap.registerPlugin(ScrollTrigger);

// State Management
const hasSeenIntro = ref(sessionStorage.getItem('has_seen_intro') === 'true');
const isLoading = ref(true);
const isError = ref(false);
const errorMessage = ref("");
const loadingPercent = ref(0);

const profileData = ref(null);
const skillData = ref([]);
const projectData = ref([]);
const certificateData = ref([]);
const experienceData = ref([]);

// --- 2. Refs untuk Element Section ---
const projectSectionRef = ref(null);
const certificateSectionRef = ref(null);
let scrollTriggerInstance = null; // Untuk menyimpan instance agar bisa dibersihkan

// Fungsi Helper untuk Preload Gambar
function preloadImage(url) {
  return new Promise((resolve) => {
    if (!url) {
      resolve();
      return;
    }
    const img = new Image();
    img.src = url;
    img.onload = resolve;
    img.onerror = resolve;
  });
}

// Karena useSWR otomatis berjalan saat setup, kita jalankan langsung di sini
const profileSWR = useSWR('cache_profile', getProfile);
const skillSWR = useSWR('cache_skills', getSkills, []);
const projectSWR = useSWR('cache_projects', () => getAllProjects({ featured: 1 }), []);
const certificateSWR = useSWR('cache_certificates', () => getAllCertificates({ featured: 1 }), []);
const experienceSWR = useSWR('cache_experiences', getAllExperiences, []);

// Watcher untuk nge-track persentase dan selesainya semua fetch
watch(
  () => [
    profileSWR.isLoading.value,
    skillSWR.isLoading.value,
    projectSWR.isLoading.value,
    certificateSWR.isLoading.value,
    experienceSWR.isLoading.value,
  ],
  (loadings) => {
    // Hitung berapa yang sudah selesai (isLoading = false)
    const completed = loadings.filter(l => !l).length;
    
    // [OPSI B] Jika ini kunjungan pertama (tab baru) dan semua data instant (SWR Cache),
    // kita tahan angkanya di 0 dulu sesaat, lalu tembak ke 100 agar animasinya jalan mulus!
    if (!hasSeenIntro.value && completed === 5 && loadingPercent.value === 0) {
      setTimeout(() => {
        loadingPercent.value = 100;
      }, 50); // Jeda sangat singkat untuk memicu CSS transition
    } else {
      loadingPercent.value = (completed / 5) * 100;
    }

    // Jika semuanya sudah false, berarti beres!
    if (completed === 5) {
      profileData.value = profileSWR.data.value;
      skillData.value = skillSWR.data.value;
      projectData.value = projectSWR.data.value;
      certificateData.value = certificateSWR.data.value;
      experienceData.value = experienceSWR.data.value;

      // Preload image HANYA JIKA ini kunjungan pertama (belum lihat intro)
      if (profileData.value?.about?.photo_url && !hasSeenIntro.value) {
        preloadImage(profileData.value.about.photo_url).then(() => {
          finalizeLoading();
        });
      } else {
        finalizeLoading();
      }
    }
  },
  { immediate: true } // langsung trigger sekali saat mount
);

function finalizeLoading() {
  if (hasSeenIntro.value) {
    // Jika sudah pernah melihat intro di sesi ini, lewati delay dan selesaikan NProgress seketika!
    NProgress.done();
    isLoading.value = false;
    window.dispatchEvent(new CustomEvent("content-loaded"));
  } else {
    // Kunjungan pertama di sesi ini: putar animasi full
    setTimeout(() => {
      isLoading.value = false;
      sessionStorage.setItem('has_seen_intro', 'true'); // Tandai sudah melihat
      hasSeenIntro.value = true; // Update state reaktif
      window.dispatchEvent(new CustomEvent("content-loaded"));
    }, 850); // Tunggu 850ms agar animasi progress bar (800ms) selesai
  }
}

// Fungsi Fetch Data Utama — dipanggil oleh Try Again button
async function fetchAllData() {
  isLoading.value = true;
  isError.value = false;
  errorMessage.value = "";
  loadingPercent.value = 0;
  
  profileSWR.revalidate();
  skillSWR.revalidate();
  projectSWR.revalidate();
  certificateSWR.revalidate();
  experienceSWR.revalidate();
}

// --- 3. Fungsi Init Animasi Stacking ---
function initStackingAnimation() {
  // Pastikan element sudah ada di DOM
  if (projectSectionRef.value && certificateSectionRef.value) {
    const projectEl = projectSectionRef.value.$el;
    const certEl = certificateSectionRef.value.$el;

    gsap.set([projectEl, certEl], {
      willChange: "transform, position",
    });
    // Pastikan Certificate berada di atas Project secara visual (z-index)
    // Project akan diam, Certificate akan jalan di atasnya
    gsap.set(certEl, {
      position: "relative",
      zIndex: 10,
    });

    // Smooth fade-out + scale-down pada Project saat Certificate mendekat
    gsap.to(projectEl, {
      opacity: 0.4,
      scale: 0.97,
      ease: "none",
      scrollTrigger: {
        trigger: projectEl,
        start: "bottom bottom",
        end: "bottom 30%",
        scrub: 0.6, // Interpolasi halus mengikuti scroll
      },
    });

    scrollTriggerInstance = ScrollTrigger.create({
      trigger: projectEl, // Element yang memicu (FeaturedProject)
      // PERUBAHAN UTAMA DISINI:
      // Menggunakan fungsi () => untuk mengecek lebar layar secara dinamis
      // Jika layar HP (< 768px): bottom-=100px (Berhenti 100px lebih awal dari bawah, memberi ruang untuk Navbar)
      // Jika Desktop: bottom bottom (Normal)
      start: () => (window.innerWidth < 768 ? "bottom bottom-=120px" : "bottom bottom"),
      end: "bottom top", // Selesai saat bagian bawah Project menyentuh atas layar
      pin: true, // Tahan (Freeze) posisi Project
      pinSpacing: false, // PENTING: Jangan beri jarak, biarkan elemen bawah (Certificate) naik menutupi
      anticipatePin: 1,
      pinType: "fixed", // Memaksa penggunaan position: fixed agar tidak bergetar
      fastScrollEnd: true, // Mencegah lonjakan posisi saat scroll cepat
      markers: false, // Ubah ke true jika ingin melihat debug markers
    });
  }
}

// --- 5. Fungsi Tracking Visitor ---
function initVisitorTracking() {
  let deviceId = localStorage.getItem('device_id');
  if (!deviceId) {
    deviceId = crypto.randomUUID();
    localStorage.setItem('device_id', deviceId);
  }
  logVisitor(deviceId).catch(err => console.error("Failed to log visitor", err));
}

// --- 4. Watcher untuk Inisialisasi Animasi ---
// Kita harus menunggu sampai isLoading false (DOM dirender) sebelum pasang animasi
watch(isLoading, (newVal) => {
  if (!newVal && !isError.value) {
    nextTick(() => {
      initStackingAnimation();
      // Refresh ScrollTrigger untuk memastikan perhitungan posisi akurat setelah render
      ScrollTrigger.refresh();

      window.dispatchEvent(new CustomEvent("app-loading-done"));
    });
  }
});

onMounted(() => {
  if (!hasSeenIntro.value) {
    // Jika ini kunjungan pertama, matikan NProgress bar biru karena kita pakai InitialLoadingScreen sendiri
    NProgress.done(); 
  }
  // fetchAllData(); <-- Dihapus! SWR sudah memanggilnya secara otomatis di setup!
  initVisitorTracking();
});

// Bersihkan ScrollTrigger saat komponen di-destroy agar tidak memory leak
onUnmounted(() => {
  if (scrollTriggerInstance) {
    scrollTriggerInstance.kill();
  }
  ScrollTrigger.getAll().forEach((t) => t.kill());
});
</script>

<template>
  <div
    class="min-h-screen bg-white text-black font-sans overflow-x-hidden flex flex-col pb-24 md:pb-0">
    <Transition name="fade">
      <InitialLoadingScreen v-if="isLoading && !hasSeenIntro" :percent="loadingPercent" />
    </Transition>

    <div
      v-if="!isLoading && isError"
      class="flex flex-col items-center justify-center min-h-screen text-center px-4 animate-in">
      <h2 class="text-2xl font-bold mb-2">Unable to Load Data</h2>

      <p class="text-gray-600 mb-6">{{ errorMessage }}</p>

      <button
        @click="fetchAllData"
        class="px-6 py-2 bg-black text-white rounded-full hover:bg-gray-800 transition-colors duration-300">
        Try Again
      </button>
    </div>

    <div v-if="!isLoading && !isError" class="animate-in">
      <Hero :profile="profileData" />
      <Tech :skills="skillData" />

      <FeaturedProject ref="projectSectionRef" :projects="projectData" class="relative z-0" />

      <FeaturedCertificate ref="certificateSectionRef" :certificates="certificateData" class="relative z-10 bg-white" />

      <Experience
        v-if="experienceData && experienceData.length > 0"
        :experiences="experienceData"
        class="relative z-20 bg-white" />

      <!-- <Philosophy /> -->
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.animate-in {
  animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
