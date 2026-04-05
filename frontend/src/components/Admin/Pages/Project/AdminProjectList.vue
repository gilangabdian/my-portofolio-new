<script setup>
import { ref, onMounted } from "vue";
import { useLocalStorage } from "@vueuse/core";
import { Icon } from "@iconify/vue";
import { getAllProjects, adminDeleteProject, adminUpdateProject } from "../../../../lib/api/ProjectApi"; // Import updateProject
import { alertSuccess, alertError, alertConfirmProject } from "../../../../lib/alert";
import { marked } from "marked";

const projects = ref([]);
const isLoading = ref(true);
const token = useLocalStorage("token", "");
const renderMarkdown = (text) => {
  if (!text) return "";
  return marked.parse(text, { breaks: true });
};

// Helper: Format enum value ke label yang readable
const formatLabel = (value) => {
  if (!value) return "";
  return value.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
};

// Helper: Format date ke "Jan 2025"
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

const fetchData = async () => {
  isLoading.value = true;
  try {
    const response = await getAllProjects();
    const result = await response.json();
    console.log(result);
    projects.value = result.data || result;
  } catch (error) {
    console.error(error);
    alertError("Gagal mengambil data project.");
  } finally {
    isLoading.value = false;
  }
};

const handleDelete = async (id) => {
  const isConfirmed = await alertConfirmProject("Yakin ingin menghapus project ini?");
  if (!isConfirmed) return;

  try {
    const response = await adminDeleteProject(token.value, id);
    if (response.ok) {
      await alertSuccess("Project berhasil dihapus! 🗑️");
      fetchData();
    } else {
      await alertError("Gagal menghapus data.");
    }
  } catch (error) {
    alertError("Terjadi kesalahan sistem.");
  }
};

// --- FITUR BARU: Toggle Featured Langsung di List ---
const handleToggleFeatured = async (project) => {
  // 1. Simpan status lama (untuk rollback jika gagal)
  const oldStatus = project.is_featured;

  // 2. Optimistic Update (Ubah UI duluan biar terasa cepat)
  project.is_featured = !oldStatus;

  try {
    // 3. Siapkan FormData (Backend butuh FormData karena method update biasanya handle file juga)
    // Kita kirim _method: PUT agar Laravel tau ini update
    const formData = new FormData();
    formData.append("_method", "PUT");
    formData.append("title", project.title);
    formData.append("description", project.description);
    formData.append("is_featured", project.is_featured ? "1" : "0");
    // Required fields by backend validation
    formData.append("start_date", project.start_date ? project.start_date.substring(0, 10) : "");
    formData.append("end_date", project.end_date ? project.end_date.substring(0, 10) : "");
    formData.append("status", project.status || "completed");

    // Panggil API Update
    const response = await adminUpdateProject(token.value, project.id, formData);

    if (!response.ok) {
      throw new Error("Gagal update");
    }
    // Tidak perlu alert sukses biar tidak mengganggu flow, cukup perubahan ikon
  } catch (error) {
    console.error(error);
    // Rollback jika gagal
    project.is_featured = oldStatus;
    alertError("Gagal mengubah status featured.");
  }
};

onMounted(() => {
  fetchData();
});
</script>

<template>
  <div class="p-6 max-w-7xl mx-auto">
    <div
      class="mb-10 border-b-4 border-black pb-4 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
      <div>
        <h1 class="text-3xl md:text-4xl font-black italic uppercase">MANAGE PROJECTS</h1>
        <p class="font-mono text-gray-600 mt-2">Edit or remove your masterpieces.</p>
      </div>

      <router-link v-if="!isLoading && projects.length > 0" to="/admin/dashboard/projects/create"
        class="bg-black text-white hover:text-black hover:bg-gray-100 border-2 border-black px-4 py-2 font-bold uppercase shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:translate-y-[2px] hover:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] active:translate-y-[4px] active:shadow-none transition-all flex items-center gap-2">
        <Icon icon="lucide:plus" class="text-xl" />
        <span>Add New</span>
      </router-link>
    </div>

    <div v-if="isLoading" class="p-8 text-center font-mono animate-pulse border-4 border-black bg-white">
      LOADING PROJECTS...
    </div>

    <div v-else-if="projects.length === 0"
      class="p-12 text-center border-4 border-black bg-white shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] flex flex-col items-center gap-4">
      <div class="bg-gray-100 p-4 rounded-full border-2 border-black">
        <Icon icon="lucide:rocket" class="text-4xl text-gray-400" />
      </div>
      <div>
        <h3 class="font-bold text-xl uppercase mb-1">No Projects Found</h3>
        <p class="font-mono text-gray-500 mb-6">You haven't launched any projects yet.</p>
        <router-link to="/admin/dashboard/projects/create"
          class="inline-flex flex-col items-center justify-center gap-1 bg-black text-white hover:text-black hover:bg-gray-100 border-2 border-black px-5 py-2 font-bold uppercase shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:scale-105 transition-transform text-sm">
          <Icon icon="lucide:plus-circle" class="text-xl" />
          <span>Launch Project!</span>
        </router-link>
      </div>
    </div>

    <div v-else>
      <div class="hidden md:block border-4 border-black bg-white shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
        <table class="w-full text-left border-collapse">
          <thead class="bg-black text-white font-mono uppercase text-sm">
            <tr>
              <th class="p-4 border-r-2 border-white w-24">Thumbnail</th>
              <th class="p-4 border-r-2 border-white">Project Info</th>
              <th class="p-4 border-r-2 border-white text-center w-28">Status</th>
              <th class="p-4 border-r-2 border-white text-center w-32">Role/Team</th>
              <th class="p-4 border-r-2 border-white text-center w-24">Featured</th>
              <th class="p-4 border-r-2 border-white w-1/4">Tech Stack</th>
              <th class="p-4 text-center w-32">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="project in projects" :key="project.id"
              class="border-b-2 border-black hover:bg-gray-50 transition-colors">
              <td class="p-4 align-top">
                <img :src="project.thumbnail_url || project.thumbnail_path"
                  class="w-24 h-16 object-cover border-2 border-black shadow-sm" />
              </td>

              <td class="p-4 align-top">
                <div class="max-w-[250px] lg:max-w-[400px]">
                  <div class="font-bold uppercase text-lg leading-tight mb-1">{{ project.title }}</div>
                  <div v-html="renderMarkdown(project.description)"
                    class="markdown-preview text-sm text-gray-500 font-mono mb-2 line-clamp-2"></div>
                  <div class="flex gap-3 mt-2">
                    <a v-if="project.repository_link" :href="project.repository_link" target="_blank"
                      class="text-gray-600 hover:text-black hover:scale-110 transition-transform">
                      <Icon icon="mdi:github" class="w-6 h-6" />
                    </a>
                    <a v-if="project.live_demo_link" :href="project.live_demo_link" target="_blank"
                      class="text-gray-600 hover:text-black underline hover:scale-110 transition-transform">
                      <Icon icon="mdi:web" class="w-6 h-6" />
                    </a>
                  </div>
                </div>
              </td>

              <td class="p-4 text-center align-top">
                <div class="flex flex-col items-center gap-1.5">
                  <span v-if="project.status"
                    class="text-[10px] font-bold uppercase px-1.5 py-0.5 border rounded-sm whitespace-nowrap"
                    :class="statusClass(project.status)">
                    {{ formatLabel(project.status) }}
                  </span>
                  <span v-if="project.type"
                    class="text-[10px] font-bold uppercase px-1.5 py-0.5 border border-black rounded-sm bg-gray-50 whitespace-nowrap">
                    {{ formatLabel(project.type) }}
                  </span>
                  <span v-if="project.start_date" class="text-[9px] font-mono text-gray-400 whitespace-nowrap">
                    {{ formatDate(project.start_date) }} → {{ formatDate(project.end_date) }}
                  </span>
                </div>
              </td>
              <td class="p-4 text-center align-top border-r-2 border-black/5 last:border-r-0">
                <div class="flex flex-col items-center gap-1">
                  <span v-if="project.role" class="text-xs font-bold uppercase truncate max-w-[120px]"
                    :title="project.role">
                    {{ project.role }}
                  </span>
                  <span v-if="project.team_size"
                    class="text-[10px] font-mono text-gray-500 bg-gray-100 px-1 rounded flex items-center gap-1">
                    <Icon icon="lucide:users" class="w-3 h-3" />
                    {{ project.team_size }} {{ project.team_size > 1 ? 'people' : 'person' }}
                  </span>
                  <span v-if="!project.role && !project.team_size" class="text-gray-300 text-xs">—</span>
                </div>
              </td>

              <td class="p-4 text-center align-top">
                <button @click="handleToggleFeatured(project)"
                  class="p-2 rounded-full hover:bg-gray-200 transition-colors"
                  :title="project.is_featured ? 'Unfeature Project' : 'Feature Project'">
                  <Icon :icon="project.is_featured ? 'lucide:star' : 'lucide:star-off'"
                    class="w-6 h-6 transition-all duration-300"
                    :class="project.is_featured ? 'text-black fill-yellow-500 scale-110' : 'text-gray-300'" />
                </button>
              </td>

              <td class="p-4 align-top">
                <div v-if="project.skills && project.skills.length" class="flex flex-wrap gap-2">
                  <span v-for="tech in project.skills" :key="tech.id"
                    class="text-xs border border-black px-2 py-1 bg-white flex items-center gap-1 font-mono">
                    <Icon :icon="tech.identifier || 'lucide:code'" class="w-4 h-4" />
                    {{ tech.name }}
                  </span>
                </div>
              </td>
              <td class="p-4 text-center align-middle">
                <div class="flex justify-center gap-2">
                  <router-link :to="`/admin/dashboard/projects/edit/${project.id}`"
                    class="bg-gray-200 hover:bg-gray-500 p-2 border-2 border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:scale-110 transition-transform">
                    <Icon icon="lucide:pencil" class="w-4 h-4" />
                  </router-link>
                  <button @click="handleDelete(project.id)"
                    class="bg-red-500 text-white hover:bg-red-600 p-2 border-2 border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:scale-110 transition-transform">
                    <Icon icon="lucide:trash-2" class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="md:hidden space-y-6">
        <div v-for="project in projects" :key="project.id + '-mobile'"
          class="border-4 border-black bg-white p-4 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] flex flex-col gap-4 relative">
          <button @click="handleToggleFeatured(project)"
            class="absolute top-2 right-2 p-2 bg-white border-2 border-black rounded-full z-10 shadow-sm">
            <Icon :icon="project.is_featured ? 'lucide:star' : 'lucide:star-off'" class="w-5 h-5"
              :class="project.is_featured ? 'text-black fill-yellow-500' : 'text-gray-300'" />
          </button>

          <div class="flex gap-4 items-start">
            <img :src="project.thumbnail_url || project.thumbnail_path"
              class="w-20 h-20 object-cover border-2 border-black flex-shrink-0 bg-gray-100" />
            <div class="flex-1 min-w-0 pr-8">
              <h3 class="font-black text-lg uppercase leading-tight break-words">{{ project.title }}</h3>
              <div v-html="renderMarkdown(project.description)"
                class="markdown-preview text-sm text-gray-500 font-mono mb-2 line-clamp-2"></div>
              <div class="flex gap-3 mt-3">
                <a v-if="project.repository_link" :href="project.repository_link" target="_blank"
                  class="w-8 h-8 flex items-center justify-center border-2 border-black bg-gray-100 hover:bg-black hover:text-white transition-colors shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] active:translate-y-[2px] active:shadow-none">
                  <Icon icon="mdi:github" class="w-5 h-5" />
                </a>
                <a v-if="project.live_demo_link" :href="project.live_demo_link" target="_blank"
                  class="w-8 h-8 flex items-center justify-center border-2 border-black bg-gray-100 hover:bg-black hover:text-white transition-colors shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] active:translate-y-[2px] active:shadow-none">
                  <Icon icon="mdi:web" class="w-5 h-5" />
                </a>
              </div>
            </div>
          </div>
          <div class="border-t-2 border-black border-dashed pt-3">
            <p class="text-xs font-bold uppercase mb-2">Tech Stack:</p>
            <div class="flex flex-wrap gap-2">
              <span v-for="tech in project.skills" :key="tech.id"
                class="text-[10px] border border-black px-1.5 py-0.5 bg-gray-50 flex items-center gap-1 font-mono">
                <Icon :icon="tech.identifier || 'lucide:code'" class="w-3 h-3" />
                {{ tech.name }}
              </span>
            </div>
          </div>
          <div class="border-t-2 border-black border-dashed pt-3">
            <div class="flex flex-wrap gap-1.5">
              <span v-if="project.status" class="text-[10px] font-bold uppercase px-1.5 py-0.5 border rounded-sm"
                :class="statusClass(project.status)">
                {{ formatLabel(project.status) }}
              </span>
              <span v-if="project.type"
                class="text-[10px] font-bold uppercase px-1.5 py-0.5 border border-black rounded-sm bg-gray-50">
                {{ formatLabel(project.type) }}
              </span>
              <span v-if="project.start_date" class="text-[10px] font-mono text-gray-400 flex items-center gap-1">
                <Icon icon="lucide:calendar" class="w-3 h-3" />
                {{ formatDate(project.start_date) }} → {{ formatDate(project.end_date) }}
              </span>
              <span v-if="project.role"
                class="text-[10px] bg-black text-white px-1.5 py-0.5 rounded-sm font-bold uppercase flex items-center gap-1">
                <Icon icon="lucide:user-cog" class="w-3 h-3" />
                {{ project.role }}
              </span>
              <span v-if="project.team_size"
                class="text-[10px] border border-black px-1.5 py-0.5 bg-gray-100 flex items-center gap-1 font-mono">
                <Icon icon="lucide:users" class="w-3 h-3" />
                Team: {{ project.team_size }}
              </span>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3 mt-2">
            <router-link :to="`/admin/dashboard/projects/edit/${project.id}`"
              class="flex items-center justify-center gap-2 bg-gray-200 border-2 border-black py-2 font-bold text-sm shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] active:shadow-none active:translate-x-[2px] active:translate-y-[2px] transition-all">
              <Icon icon="lucide:pencil" />
              Edit
            </router-link>
            <button @click="handleDelete(project.id)"
              class="bg-red-500 text-white hover:bg-red-600 flex items-center justify-center gap-2 border-2 border-black py-2 font-bold text-sm shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] active:shadow-none active:translate-x-[2px] active:translate-y-[2px] transition-all">
              <Icon icon="lucide:trash-2" />
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
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
}

.markdown-preview :deep(p) {
  margin-bottom: 0.5rem;
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