<script setup>
import { onMounted, ref, nextTick, watch } from "vue";
import NProgress from "nprogress";
import "nprogress/nprogress.css";
import { alertError } from "../../lib/alert";
import { getAllCertificates } from "../../lib/api/CertificateApi";
import { useSWR } from "../../utils/useSWR";
import { Icon } from "@iconify/vue";
import gsap from "gsap";
import { marked } from "marked";

const certificates = ref([]);
const loading = ref(true);

// State untuk Modal Detail
const isModalOpen = ref(false);
const selectedCert = ref(null);
const renderMarkdown = (text) => {
  if (!text) return "";
  return marked.parse(text, { breaks: true });
};

// Helper: Format enum value to readable label
const formatLabel = (value) => {
  if (!value) return "";
  return value.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
};

// Helper: Format date to "Jan 2025"
const formatDate = (dateStr) => {
  if (!dateStr) return "";
  const d = new Date(dateStr);
  return d.toLocaleDateString("en-US", { month: "short", year: "numeric" });
};

// --- SWR Caching ---
const { data: certificateSWR, isLoading: isSWRloading, revalidate } = useSWR('cache_all_certificates', getAllCertificates, []);

watch(isSWRloading, async (newVal) => {
  if (!newVal) {
    certificates.value = certificateSWR.value;
    NProgress.done();
    // Delay buatan 800ms agar transisi smooth
    setTimeout(() => {
      loading.value = false;
      window.dispatchEvent(new CustomEvent("content-loaded"));
    }, 800);
  }
}, { immediate: true });

// --- ANIMATION TRIGGER (Menggunakan Watcher) ---
watch(loading, (newVal) => {
  if (!newVal) {
    nextTick(() => {
      const tl = gsap.timeline();

      // 1. Animasi Header
      tl.fromTo(
        ".page-title",
        { y: 30, autoAlpha: 0 },
        {
          y: 0,
          autoAlpha: 1,
          duration: 0.8,
          ease: "power2.out",
        },
      );

      // 2. Animasi Kartu (Jika ada data)
      if (certificates.value.length > 0) {
        tl.fromTo(
          ".cert-card",
          { y: 30, autoAlpha: 0 },
          {
            y: 0,
            autoAlpha: 1,
            duration: 0.8,
            stagger: 0.1,
            ease: "power2.out",
          },
          "-=0.6", // Overlap dengan header
        );
      }
    });
  }
});

// --- MODAL LOGIC (Tidak Berubah) ---
function openModal(cert) {
  selectedCert.value = cert;
  isModalOpen.value = true;
  document.body.style.overflow = "hidden";
}

function closeModal() {
  isModalOpen.value = false;
  document.body.style.overflow = "auto";
  setTimeout(() => {
    selectedCert.value = null;
  }, 200);
}

onMounted(() => {
  // Data load handled by SWR watcher
});
</script>

<template>
  <div class="min-h-screen mb-40">


    <div v-if="!loading" class="-mt-16 md:mt-4 px-4 py-16 md:px-8 max-w-6xl mx-auto">
      <div class="text-center mb-12 mt-4 page-title" style="opacity: 0; visibility: hidden">
        <h1 class="anim-text text-2xl md:text-3xl font-bold tracking-wide text-black">
          All Certificates
        </h1>
        <p class="mt-4 font-sans text-gray-700 text-sm md:text-base max-w-xl mx-auto italic">
          "A complete archive of validated skills, workshops, and achievements."
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-20">
        <div v-for="certificate in certificates" :key="certificate.id" @click="openModal(certificate)"
          class="cert-card group flex flex-col p-3 bg-white rounded-xl border border-black/20 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-200 cursor-pointer"
          style="opacity: 0; visibility: hidden">
          <div
            class="w-full aspect-video bg-gray-50 border border-black/10 rounded-lg mb-3 overflow-hidden relative flex items-center justify-center p-2">
            <img loading="lazy" :src="certificate.image_url" :alt="certificate.title"
              class="w-full h-full object-contain transition-transform duration-300 group-hover:scale-105" />
          </div>

          <div class="flex flex-col flex-grow px-1">
            <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wider mb-1">
              {{ certificate.issuer }}
            </span>
            <h3
              class="text-sm font-bold font-serif leading-tight group-hover:underline decoration-2 underline-offset-2">
              {{ certificate.title }}
            </h3>
          </div>
        </div>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="isModalOpen" class="fixed inset-0 z-[999] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="closeModal"></div>

        <div
          class="relative bg-white w-full max-w-2xl max-h-[85vh] flex flex-col rounded-xl border border-black/20 shadow-xl animate-in fade-in zoom-in duration-200">
          <div class="flex justify-between items-start p-6 border-b border-black/10 bg-gray-50 rounded-t-lg shrink-0">
            <div>
              <h3 class="text-2xl font-black font-serif uppercase pr-4 leading-none mb-2">
                {{ selectedCert?.title }}
              </h3>

              <div class="flex flex-wrap gap-2 mb-2 mt-1">
                <span
                  class="text-xs md:text-sm font-bold bg-white text-gray-500 px-2 py-1 border border-black/10 shadow-sm rounded-sm flex items-center gap-1.5">
                  <span class="text-[10px] text-gray-500 uppercase tracking-wider font-mono font-normal">Issuer:</span>
                  {{ selectedCert?.issuer }}
                </span>
                <span v-if="selectedCert?.type"
                  class="text-xs md:text-sm font-bold px-2 py-1 border border-black/10 rounded-sm bg-white flex items-center gap-1.5 shadow-sm">
                  <span class="text-[10px] text-gray-500 uppercase tracking-wider font-mono font-normal">Type:</span>
                  {{ formatLabel(selectedCert.type) }}
                </span>
              </div>

              <span v-if="selectedCert?.start_date"
                class="text-xs font-mono text-gray-500 flex items-center gap-1 mt-1">
                <span class="text-[10px] uppercase tracking-wider font-mono font-normal">Period:</span>
                <Icon icon="lucide:calendar" class="w-3.5 h-3.5" />
                {{ formatDate(selectedCert.start_date) }} → {{ formatDate(selectedCert.end_date) }}
              </span>
            </div>

            <button @click="closeModal"
              class="hidden md:block p-1 bg-red-500 hover:bg-red-600 border border-transparent text-white transition-colors rounded-full shrink-0 shadow-sm">
              <Icon icon="mdi:close" class="text-xl" />
            </button>
          </div>

          <div class="p-6 overflow-y-auto custom-scrollbar">
            <div
              class="w-full aspect-video bg-gray-50 border border-black/10 rounded-lg mb-6 overflow-hidden flex-shrink-0 flex items-center justify-center p-4">
              <img loading="lazy" :src="selectedCert?.image_url" :alt="selectedCert?.title" class="w-full h-full object-contain" />
            </div>

            <h4 class="font-bold font-serif uppercase text-sm mb-3 border-b border-black/20 inline-block">
              Description
            </h4>
            <div v-html="renderMarkdown(selectedCert?.description)"
              class="markdown-preview font-mono text-sm md:text-base text-gray-700 leading-relaxed"></div>
          </div>

          <div class="p-6 border-t border-black/10 bg-gray-50 rounded-b-lg shrink-0">
            <div class="flex flex-col gap-3">
              <a v-if="selectedCert?.credential_link" :href="selectedCert?.credential_link" target="_blank"
                class="flex items-center justify-center gap-2 w-full py-3 text-sm font-bold border border-transparent rounded bg-black hover:bg-black/90 text-white dark:bg-white dark:hover:bg-gray-200 dark:!text-black transition-colors shadow-sm">
                <Icon icon="mdi:certificate-outline" class="text-xl" />
                Verify Credential
              </a>

              <button @click="closeModal"
                class="w-full py-3 text-sm font-bold uppercase tracking-wider text-white bg-red-500 border border-transparent rounded hover:bg-red-600 transition-colors flex items-center justify-center gap-2 shadow-sm">
                <Icon icon="mdi:close-circle-outline" class="text-xl" />
                Close Details
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
/* Transisi Fade untuk Loading */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.6s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Custom Scrollbar untuk Modal - Dirampingkan */
.custom-scrollbar::-webkit-scrollbar {
  width: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-left: 2px solid black;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: black;
  border: 1px solid white;
  border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #333;
}

/* Styling khusus untuk isi konten Markdown di Modal */
.markdown-preview :deep(ul) {
  list-style-type: disc !important;
  margin-left: 1.5rem !important;
  margin-bottom: 0.5rem !important;
}

.markdown-preview :deep(ol) {
  list-style-type: decimal !important;
  margin-left: 1.5rem !important;
  margin-bottom: 0.5rem !important;
}

.markdown-preview :deep(li) {
  display: list-item !important;
  margin-bottom: 0.25rem;
}

.markdown-preview :deep(p) {
  margin-bottom: 0.75rem;
}

.markdown-preview :deep(strong),
.markdown-preview :deep(b) {
  font-weight: 900 !important;
}

.markdown-preview :deep(em),
.markdown-preview :deep(i) {
  font-style: italic !important;
}
</style>