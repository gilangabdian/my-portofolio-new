<script setup>
import { onMounted, ref, nextTick, watch } from "vue";
import NProgress from "nprogress";
import "nprogress/nprogress.css";
import { alertError } from "../../lib/alert";
import { getAllProjects } from "../../lib/api/ProjectApi";
import { Icon } from "@iconify/vue";
import gsap from "gsap";
import { marked } from "marked";

const projects = ref([]);
const loading = ref(true);

// State untuk Modal Detail
const isModalOpen = ref(false);
const selectedProject = ref(null);
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

// Helper: Status badge color class
const statusClass = (status) => {
  const map = {
    completed: "bg-green-100 text-green-800 border-green-600",
    in_development: "bg-yellow-100 text-yellow-800 border-yellow-600",
    on_hold: "bg-gray-100 text-gray-700 border-gray-500",
    cancelled: "bg-red-100 text-red-800 border-red-600",
  };
  return map[status] || "bg-gray-100 text-gray-700 border-gray-500";
};

// --- FUNCTION FETCH DATA (Dengan Delay Buatan agar Smooth) ---
async function fetchProjects() {
  loading.value = true;
  NProgress.start();
  try {
    const response = await getAllProjects();
    const responseBody = await response.json();

    if (response.status === 200) {
      projects.value = responseBody.data || responseBody;
    } else {
      await alertError(responseBody.message);
    }
  } catch (e) {
    console.error(`Error fetch projects:`, e);
  } finally {
    NProgress.done();
    // Delay 800ms sebelum loading hilang agar transisi tidak kasar
    setTimeout(() => {
      loading.value = false;
      window.dispatchEvent(new CustomEvent("content-loaded"));
    }, 800);
  }
}

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

      // 2. Animasi Kartu Project
      if (projects.value.length > 0) {
        tl.fromTo(
          ".project-card",
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
function openModal(project) {
  selectedProject.value = project;
  isModalOpen.value = true;
  document.body.style.overflow = "hidden";
}

function closeModal() {
  isModalOpen.value = false;
  document.body.style.overflow = "auto";
  setTimeout(() => {
    selectedProject.value = null;
  }, 200);
}

onMounted(async () => {
  await fetchProjects();
});
</script>

<template>
  <div class="min-h-screen mb-40">
    <div class="px-4 py-16 md:px-8 max-w-6xl mx-auto">


      <div v-if="!loading">
        <div class="text-center mb-12 -mt-12 md:mt-7 page-title" style="opacity: 0; visibility: hidden">
          <h1 class="anim-text text-2xl md:text-3xl font-bold tracking-wide text-black">
            All Projects
          </h1>
          <p class="mt-4 font-sans text-gray-600 text-sm md:text-base max-w-xl mx-auto italic">
            "A collection of crafted code, deployed solutions, and creative experiments."
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-20">
          <div v-for="project in projects" :key="project.id" @click="openModal(project)"
            class="project-card group flex flex-col p-3 bg-white rounded-xl border border-black/20 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-200 cursor-pointer"
            style="opacity: 0; visibility: hidden">
            <div
              class="w-full aspect-video bg-gray-50 border border-black/10 rounded-lg mb-3 overflow-hidden relative flex items-center justify-center">
              <img v-if="project.thumbnail_url" :src="project.thumbnail_url" :alt="project.title"
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" />
              <div v-else class="flex flex-col items-center justify-center w-full h-full text-gray-400">
                <Icon icon="mdi:image-off-outline" class="text-3xl mb-2" />
                <span class="text-[10px] font-bold uppercase">No Preview</span>
              </div>
            </div>

            <div class="flex flex-col flex-grow px-1">
              <h3
                class="text-base font-bold font-serif leading-tight mb-2 group-hover:underline decoration-2 underline-offset-2">
                {{ project.title }}
              </h3>

              <div v-if="project.skills && project.skills.length > 0" class="flex flex-wrap gap-1.5 mt-auto">
                <div v-for="skill in project.skills.slice(0, 3)" :key="skill.id || skill"
                  class="px-2 py-0.5 border border-black/10 rounded bg-gray-50 flex items-center gap-1 shadow-sm opacity-80 group-hover:opacity-100 transition-opacity"
                  :title="skill.name || skill">
                  <Icon v-if="skill.identifier" :icon="skill.identifier" class="w-3 h-3 text-black" />
                  <span class="text-[9px] font-bold uppercase tracking-wide leading-none">{{ skill.name || skill
                  }}</span>
                </div>
                <!-- Indicator +X if skills > 3 -->
                <div v-if="project.skills.length > 3"
                  class="px-1.5 py-0.5 border border-transparent rounded bg-gray-100 text-gray-500 flex items-center justify-center shadow-sm opacity-80 group-hover:opacity-100 transition-opacity"
                  :title="project.skills.slice(3).map(s => s.name || s).join(', ')">
                  <span class="text-[9px] font-bold uppercase tracking-wide leading-none">+{{ project.skills.length - 3
                  }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="isModalOpen" class="fixed inset-0 z-[999] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="closeModal"></div>

        <div
          class="relative bg-white w-full max-w-2xl max-h-[85vh] flex flex-col rounded-xl border border-black/20 shadow-2xl animate-in fade-in zoom-in duration-200">
          <div class="flex justify-between items-start p-6 border-b border-black/10 bg-gray-50 rounded-t-lg shrink-0">
            <h3 class="text-2xl font-black font-serif uppercase pr-4 leading-none">
              {{ selectedProject?.title }}
            </h3>

            <button @click="closeModal"
              class="hidden md:block p-1.5 bg-red-50 text-red-600 hover:bg-red-100 transition-colors rounded-full shrink-0 shadow-sm">
              <Icon icon="mdi:close" class="text-xl" />
            </button>
          </div>

          <div class="p-6 overflow-y-auto custom-scrollbar" data-lenis-prevent>
            <div
              class="w-full aspect-video bg-gray-50 border border-black/10 rounded-lg mb-6 overflow-hidden flex-shrink-0">
              <img v-if="selectedProject?.thumbnail_url" :src="selectedProject?.thumbnail_url"
                :alt="selectedProject?.title" class="w-full h-full object-cover" />
              <div v-else class="flex w-full h-full items-center justify-center text-gray-400">
                <Icon icon="mdi:image-off-outline" class="text-4xl" />
              </div>
            </div>

            <div v-if="selectedProject?.skills && selectedProject.skills.length > 0" class="mb-6">
              <h4 class="font-bold font-serif uppercase text-sm mb-3 border-b border-black/20 inline-block">
                Tech Stack
              </h4>
              <div class="flex flex-wrap gap-2">
                <div v-for="skill in selectedProject.skills" :key="skill.id || skill"
                  class="flex items-center gap-2 px-3 py-1.5 border border-black/10 rounded-md bg-gray-50 shadow-sm text-xs font-bold uppercase transition-transform hover:-translate-y-0.5 cursor-default text-gray-600">
                  <Icon v-if="skill.identifier" :icon="skill.identifier" class="text-base" />
                  {{ skill.name || skill }}
                </div>
              </div>
            </div>

            <div class="flex flex-wrap gap-2 mb-4">
              <span v-if="selectedProject?.status"
                class="text-xs font-bold uppercase px-2 py-1 border-2 rounded-sm flex items-center gap-1.5"
                :class="statusClass(selectedProject.status)">
                <span class="text-[10px] text-gray-500 uppercase tracking-wider font-mono font-normal">Status:</span>
                {{ formatLabel(selectedProject.status) }}
              </span>
              <span v-if="selectedProject?.type"
                class="text-xs font-bold uppercase px-2 py-1 border border-black/10 rounded-sm bg-gray-50 flex items-center gap-1.5">
                <span class="text-[10px] text-gray-500 uppercase tracking-wider font-mono font-normal">Type:</span>
                {{ formatLabel(selectedProject.type) }}
              </span>
              <span v-if="selectedProject?.start_date"
                class="text-xs font-mono text-gray-500 flex items-center gap-1 px-2 py-1">
                <span class="text-[10px] uppercase tracking-wider font-mono font-normal">Period:</span>
                <Icon icon="lucide:calendar" class="w-3.5 h-3.5" />
                {{ formatDate(selectedProject.start_date) }} → {{ formatDate(selectedProject.end_date) }}
              </span>
            </div>

            <div v-if="selectedProject?.role || selectedProject?.team_size"
              class="mb-6 p-4 border border-black/10 bg-gray-50/50 rounded-lg flex flex-wrap gap-6 text-gray-600">
              <div v-if="selectedProject?.role">
                <h4 class="text-[10px] font-black uppercase text-gray-400 mb-1">Role:</h4>
                <div class="flex items-center gap-2 font-bold text-sm uppercase">
                  <Icon icon="lucide:user-cog" class="text-lg" />
                  {{ selectedProject.role }}
                </div>
              </div>
              <div v-if="selectedProject?.team_size">
                <h4 class="text-[10px] font-black uppercase text-gray-400 mb-1">Team Size:</h4>
                <div class="flex items-center gap-2 font-bold text-sm uppercase">
                  <Icon icon="lucide:users" class="text-lg" />
                  {{ selectedProject.team_size }} {{ selectedProject.team_size > 1 ? 'People' : 'Person' }}
                </div>
              </div>
            </div>

            <h4 class="font-bold font-serif uppercase text-sm mb-3 border-b border-black/20 inline-block">
              Description
            </h4>
            <div v-html="renderMarkdown(selectedProject?.description)"
              class="markdown-preview font-mono text-sm md:text-base text-gray-700 leading-relaxed"></div>
          </div>

          <div class="p-6 border-t border-black/10 bg-gray-50 rounded-b-lg shrink-0">
            <div class="flex flex-col gap-3">
              <div class="flex gap-3">
                <a v-if="selectedProject?.repository_link" :href="selectedProject?.repository_link" target="_blank"
                  class="flex-1 flex items-center justify-center gap-2 py-3 text-sm font-bold border border-black/20 rounded-lg bg-white hover:bg-gray-100 text-black transition-colors shadow-sm">
                  <Icon icon="mdi:github" class="text-xl" />
                  View Code
                </a>
                <a v-if="selectedProject?.live_demo_link" :href="selectedProject?.live_demo_link" target="_blank"
                  class="flex-1 flex items-center justify-center gap-2 py-3 text-sm font-bold border border-transparent rounded-lg bg-black hover:bg-black/80 text-white dark:bg-white dark:hover:bg-gray-200 dark:!text-black transition-colors shadow-sm">
                  <Icon icon="mdi:external-link" class="text-xl" />
                  Live Demo
                </a>
              </div>

              <button @click="closeModal"
                class="w-full py-3 text-sm font-bold uppercase tracking-wider text-white bg-red-500 border border-transparent rounded-lg hover:bg-red-600 transition-colors flex items-center justify-center gap-2 shadow-sm">
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
/* Tambahan Style Transisi */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.6s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Custom Scrollbar untuk Modal - Sedikit dirampingkan */
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