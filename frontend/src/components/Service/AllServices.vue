<script setup>
import { onMounted, ref, nextTick, watch } from "vue";
import NProgress from "nprogress";
import "nprogress/nprogress.css";
import { alertError } from "../../lib/alert";
import { getAllServices } from "../../lib/api/ServiceApi";
import { Icon } from "@iconify/vue";
import gsap from "gsap";
import { marked } from "marked";

const services = ref([]);
const loading = ref(true);

// State untuk Modal Detail
const isModalOpen = ref(false);
const selectedService = ref(null);
const renderMarkdown = (text) => {
  if (!text) return "";
  return marked.parse(text, { breaks: true });
};

// --- FUNCTION FETCH DATA ---
async function fetchServices() {
  loading.value = true;
  NProgress.start();
  try {
    const response = await getAllServices();
    const responseBody = await response.json();

    if (response.status === 200) {
      services.value = responseBody.data || responseBody;
    } else {
      await alertError(responseBody.message);
    }
  } catch (e) {
    console.error(`Error fetch services:`, e);
  } finally {
    NProgress.done();
    // Delay buatan seperti About.vue agar transisi smooth
    setTimeout(() => {
      loading.value = false;
      window.dispatchEvent(new CustomEvent("content-loaded"));
    }, 800);
  }
}

// --- ANIMATION TRIGGER ---
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

      // 2. Animasi Kartu Service atau Coming Soon
      if (services.value.length > 0) {
        tl.fromTo(
          ".service-card",
          { y: 30, autoAlpha: 0 },
          {
            y: 0,
            autoAlpha: 1,
            duration: 0.8,
            stagger: 0.1,
            ease: "power2.out",
          },
          "-=0.6",
        );
      } else {
        // Animasi untuk box Coming Soon
        tl.fromTo(
          ".coming-soon-box",
          { y: 30, autoAlpha: 0 },
          {
            y: 0,
            autoAlpha: 1,
            duration: 0.8,
            ease: "power2.out",
          },
          "-=0.6",
        );
      }
    });
  }
});

// --- MODAL LOGIC ---
function openModal(service) {
  selectedService.value = service;
  isModalOpen.value = true;
  document.body.style.overflow = "hidden";
}

function closeModal() {
  isModalOpen.value = false;
  document.body.style.overflow = "auto";
  setTimeout(() => {
    selectedService.value = null;
  }, 200);
}

// --- ORDER / CTA LOGIC ---
function getOrderLink(service) {
  if (service?.cta_link && service.cta_link.startsWith("http")) {
    return service.cta_link;
  }
  const phoneNumber = "6285798124873";
  const message = `Hello Abdian, I am interested in your service: *${service?.title}*.\n\nCould we discuss the details and requirements for this project?`;

  return `https://wa.me/${phoneNumber}?text=${encodeURIComponent(message)}`;
}
onMounted(async () => {
  await fetchServices();
});
</script>

<template>
  <div class="min-h-screen mb-40">


    <div v-if="!loading" class="-mt-16 md:mt-3 px-4 py-16 md:px-8 max-w-6xl mx-auto">
      <div class="text-center mb-12 mt-4 page-title " style="opacity: 0; visibility: hidden">
        <h1 class="anim-text text-2xl md:text-3xl font-bold tracking-wide text-black">
          All Services
        </h1>
        <p class="mt-4 font-sans text-gray-600 text-sm md:text-base max-w-xl mx-auto italic">
          "Professional solutions tailored to your needs. Ready to collaborate?"
        </p>
      </div>

      <div v-if="services.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-20">
        <div v-for="service in services" :key="service.id" @click="openModal(service)"
          class="service-card group flex flex-col bg-white border border-black/20 rounded-xl p-4 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-200 cursor-pointer"
          style="opacity: 0; visibility: hidden">

          <div class="flex flex-col items-center justify-center text-center h-full py-2">
            <div
              class="w-14 h-14 mb-4 bg-gray-50 text-black flex items-center justify-center rounded-lg border border-black/10 shadow-sm group-hover:scale-110 transition-transform duration-300">
              <Icon :icon="service.icon || 'mdi:briefcase-outline'" class="text-3xl" />
            </div>

            <h3
              class="text-base font-bold font-serif leading-tight mb-2 group-hover:underline decoration-2 underline-offset-2">
              {{ service.title }}
            </h3>

            <span
              class="mt-auto inline-block bg-gray-50 border border-black/10 px-2 py-0.5 text-[10px] md:text-xs font-bold uppercase rounded shadow-sm">
              {{ service.price }}
            </span>
          </div>
        </div>
      </div>

      <div v-else
        class="coming-soon-box flex flex-col items-center justify-center py-16 px-6 text-center border border-black/20 bg-gray-50/50 rounded-lg shadow-sm max-w-2xl mx-auto mb-20"
        style="opacity: 0; visibility: hidden">
        <div
          class="w-16 h-16 bg-black text-white flex items-center justify-center rounded-lg border-2 border-black shadow-[2px_2px_0px_0px_#9ca3af] mb-4">
          <Icon icon="mdi:cone" class="text-3xl" />
        </div>
        <h1 class="anim-text text-2xl md:text-3xl font-bold tracking-wide text-black">
          Coming Soon
        </h1>
        <p class="font-sans text-gray-600 text-sm md:text-base font-medium max-w-md leading-relaxed mb-6">
          I am currently cooking up some exciting new service packages. They are being carefully crafted and will be
          available here shortly!
        </p>
        <router-link to="/contacts"
          class="inline-flex items-center gap-2 py-2 px-4 text-xs md:text-sm font-bold uppercase border border-transparent rounded bg-black text-white hover:bg-black/80 transition-all shadow-sm active:scale-95">
          <Icon icon="mdi:email-outline" class="text-base" />
          Discuss Custom Project
        </router-link>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="isModalOpen" class="fixed inset-0 z-[999] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="closeModal"></div>

        <div
          class="relative bg-white w-full max-w-lg max-h-[90vh] flex flex-col rounded-lg border border-black/20 shadow-xl animate-in fade-in zoom-in duration-200">
          <div
            class="flex justify-between items-start p-4 md:p-5 border-b border-black/10 bg-gray-50 rounded-t-md shrink-0">
            <div class="flex items-center gap-3">
              <div
                class="w-10 h-10 bg-white text-black flex items-center justify-center rounded border border-black/20 shrink-0 shadow-sm">
                <Icon :icon="selectedService?.icon || 'mdi:briefcase-outline'" class="text-xl" />
              </div>
              <div>
                <h3 class="text-lg md:text-xl font-black font-serif uppercase leading-none mb-1">
                  {{ selectedService?.title }}
                </h3>
                <span class="text-xs font-bold text-gray-500">
                  Starting at:
                  <span class="text-gray-700 bg-gray-50 px-1 border border-black/10">{{ selectedService?.price }}</span>
                </span>
              </div>
            </div>
            <button @click="closeModal"
              class="p-1.5 bg-red-50 text-red-600 hover:bg-red-100 transition-colors rounded shadow-sm">
              <Icon icon="mdi:close" class="text-lg" />
            </button>
          </div>

          <div class="p-4 md:p-5 overflow-y-auto custom-scrollbar bg-white" data-lenis-prevent>
            <h4 class="font-bold uppercase text-xs mb-2 text-gray-400">Description</h4>
            <div v-html="renderMarkdown(selectedService?.description)"
              class="markdown-preview font-mono text-sm md:text-base text-gray-700 leading-relaxed"></div>
          </div>

          <div class="p-4 md:p-5 border-t border-black/10 bg-gray-50 rounded-b-md shrink-0 flex gap-2.5">
            <a :href="getOrderLink(selectedService)" target="_blank"
              class="flex-1 flex items-center justify-center gap-2 py-2 text-xs md:text-sm font-bold border border-transparent rounded bg-black text-white hover:bg-black/80 transition-all shadow-sm active:scale-95">
              <Icon icon="mdi:message-text-outline" class="text-lg" />
              I'm Interested
            </a>

            <button @click="closeModal"
              class="flex-1 flex items-center justify-center gap-2 py-2 text-xs md:text-sm font-bold text-white bg-red-500 border border-transparent rounded hover:bg-red-600 transition-all shadow-sm active:scale-95">
              Close
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
/* Transisi Fade */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.6s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Scrollbar Dirampingkan */
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