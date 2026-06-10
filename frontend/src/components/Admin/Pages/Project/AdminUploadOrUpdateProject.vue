<script setup>
import { useLocalStorage } from "@vueuse/core";
import { reactive, ref, onMounted, computed, onBeforeUnmount } from "vue";
import { useRoute, useRouter } from "vue-router";
import { Icon } from "@iconify/vue";
import { adminUploadProject, adminUpdateProject, getSingleProject } from "../../../../lib/api/ProjectApi";
import { getSkills } from "../../../../lib/api/SkillApi";
import { alertSuccess, alertError } from "../../../../lib/alert";
import { marked } from "marked";

const route = useRoute();
const router = useRouter();
const token = useLocalStorage("token", "");

// --- KONFIGURASI URL STORAGE ---
const storageUrl = import.meta.env.VITE_STORAGE_URL || "http://localhost:8000/storage/";

// --- DETEKSI MODE (EDIT ATAU BARU) ---
const projectId = route.params.id;
const isEditMode = computed(() => !!projectId);

const skills = ref([]);
const selectedSkillIds = ref([]);
const isLoading = ref(false);
const isFetchingSkills = ref(true);

// Options untuk dropdown status dan type
const statusOptions = [
  { value: 'completed', label: 'Completed' },
  { value: 'in_development', label: 'In Development' },
  { value: 'on_hold', label: 'On Hold' },
  { value: 'cancelled', label: 'Cancelled' },
];
const typeOptions = [
  { value: 'web_development', label: 'Web Development' },
  { value: 'mobile_development', label: 'Mobile Development' },
  { value: 'desktop_application', label: 'Desktop Application' },
  { value: 'game_development', label: 'Game Development' },
];

const form = reactive({
  title: "",
  description: "",
  repository_link: "",
  live_demo_link: "",
  is_featured: false,
  start_date: "",
  end_date: "",
  status: "completed",
  type: "",
  team_size: null,
  role: "",
});

const file = ref(null);
const previewImage = ref(null);
const renderMarkdown = (text) => {
  if (!text) return "";
  return marked.parse(text, { breaks: true });
};

// --- CUSTOM DROPDOWN STATE ---
const isStatusDropdownOpen = ref(false);
const isTypeDropdownOpen = ref(false);
const dropdownStatusRef = ref(null);
const dropdownTypeRef = ref(null);

const selectStatus = (value) => {
  form.status = value;
  isStatusDropdownOpen.value = false;
};
const selectType = (value) => {
  form.type = value;
  isTypeDropdownOpen.value = false;
};

const handleClickOutside = (event) => {
  if (isStatusDropdownOpen.value && dropdownStatusRef.value && !dropdownStatusRef.value.contains(event.target)) {
    isStatusDropdownOpen.value = false;
  }
  if (isTypeDropdownOpen.value && dropdownTypeRef.value && !dropdownTypeRef.value.contains(event.target)) {
    isTypeDropdownOpen.value = false;
  }
};

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
});

// --- FETCH DATA ---
onMounted(async () => {
  document.addEventListener("click", handleClickOutside);

  try {
    const response = await getSkills();
    const result = await response.json();

    if (Array.isArray(result)) {
      skills.value = result;
    } else if (result.data) {
      skills.value = result.data;
    }
  } catch (error) {
    console.error("Error fetching skills:", error);
    alertError("Gagal memuat skill.");
  } finally {
    isFetchingSkills.value = false;
  }

  if (isEditMode.value) {
    try {
      const response = await getSingleProject(projectId);
      const result = await response.json();
      console.log(result);
      const data = result.data || result;

      form.title = data.title;
      form.description = data.description;
      form.repository_link = data.repository_link;
      form.live_demo_link = data.live_demo_link;
      form.is_featured = data.is_featured ? true : false;
      // Load kolom baru
      form.start_date = data.start_date ? data.start_date.substring(0, 10) : "";
      form.end_date = data.end_date ? data.end_date.substring(0, 10) : "";
      form.status = data.status || "completed";
      form.type = data.type || "";
      form.team_size = data.team_size || null;
      form.role = data.role || "";

      if (data.skills && Array.isArray(data.skills)) {
        selectedSkillIds.value = data.skills.map((item) => item.id);
      }

      if (data.thumbnail_path) {
        previewImage.value = data.thumbnail_url;
      }
    } catch (error) {
      console.error(error);
      alertError("Gagal mengambil data project.");
      router.push("/admin/dashboard/projects");
    }
  }
});

// --- HANDLE SELECTION ---
const toggleSkill = (id) => {
  if (selectedSkillIds.value.includes(id)) {
    selectedSkillIds.value = selectedSkillIds.value.filter((s) => s !== id);
  } else {
    selectedSkillIds.value.push(id);
  }
};

const handleFileChange = (e) => {
  const selectedFile = e.target.files[0];
  if (selectedFile) {
    file.value = selectedFile;
    previewImage.value = URL.createObjectURL(selectedFile);
  }
};

const removeImage = () => {
  previewImage.value = null;
  file.value = null;
  const input = document.getElementById("file-upload");
  if (input) input.value = "";
};

// --- SUBMIT ---
async function handleSubmit() {
  if (!form.title) {
    alertError("Judul wajib diisi!");
    return;
  }
  if (!isEditMode.value && !file.value) {
    alertError("Thumbnail wajib diisi untuk project baru!");
    return;
  }
  if (selectedSkillIds.value.length === 0) {
    alertError("Pilih minimal satu Tech Stack!");
    return;
  }

  isLoading.value = true;
  try {
    const formData = new FormData();
    formData.append("title", form.title);
    formData.append("description", form.description || "");
    formData.append("repository_link", form.repository_link || "");
    formData.append("live_demo_link", form.live_demo_link || "");
    formData.append("is_featured", form.is_featured ? "1" : "0");
    // Append kolom baru
    formData.append("start_date", form.start_date);
    formData.append("end_date", form.end_date);
    formData.append("status", form.status);
    formData.append("type", form.type || "");
    formData.append("team_size", form.team_size || "");
    formData.append("role", form.role || "");

    if (file.value) {
      formData.append("thumbnail", file.value);
    }

    selectedSkillIds.value.forEach((id) => {
      formData.append("tech_stack_ids[]", id);
    });

    let response;
    if (isEditMode.value) {
      formData.append("_method", "PUT");
      response = await adminUpdateProject(token.value, projectId, formData);
    } else {
      response = await adminUploadProject(token.value, formData);
    }

    const responseBody = await response.json();

    if (response.ok || response.status === 201 || response.status === 200) {
      await alertSuccess(isEditMode.value ? "Project berhasil diupdate! 🚀" : "Project berhasil diluncurkan! 🚀");
      router.push("/admin/dashboard/projects");
    } else {
      await alertError(responseBody.message || "Gagal menyimpan project");
    }
  } catch (e) {
    console.error(e);
    alertError("Terjadi kesalahan sistem.");
  } finally {
    isLoading.value = false;
  }
}
</script>

<template>
  <div class="p-6 max-w-7xl mx-auto">
    <div class="mb-8">
      <router-link
        to="/admin/dashboard/projects"
        class="inline-flex items-center gap-2 font-bold font-mono text-sm mb-4 hover:underline hover:text-gray-500 transition-colors">
        <Icon icon="lucide:arrow-left" class="text-lg" />
        BACK TO LIST
      </router-link>

      <div class="border-b-4 border-black pb-4 flex justify-between items-end">
        <div>
          <h1 class="text-3xl md:text-4xl font-black italic uppercase">
            {{ isEditMode ? "EDIT PROJECT" : "UPLOAD PROJECT" }}
          </h1>
          <p class="font-mono text-gray-600 mt-2 text-sm md:text-base">
            {{ isEditMode ? "Refine your masterpiece." : "Showcase your latest masterpiece." }}
          </p>
        </div>
        <div
          class="hidden md:block bg-black text-white px-3 py-1 font-mono font-bold uppercase transform rotate-2 shadow-[4px_4px_0px_0px_rgba(200,200,200,1)]">
          {{ isEditMode ? "Update Mode" : "Create New" }}
        </div>
      </div>
    </div>

    <div class="border-4 border-black p-6 md:p-8 shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] bg-white mb-12">
      <form @submit.prevent="handleSubmit" class="flex flex-col lg:flex-row gap-8">
        <div class="flex-1 space-y-6">
          <div>
            <label class="block font-black mb-2 border-b-2 border-black inline-block text-sm uppercase">
              Project Title
              <span class="text-black">*</span>
            </label>
            <input
              v-model="form.title"
              type="text"
              placeholder="e.g. THE NEXT BIG APP"
              class="w-full p-4 border-2 border-black font-bold focus:bg-gray-50 focus:outline-none transition-colors shadow-[4px_4px_0px_0px_rgba(0,0,0,0)] focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] placeholder:font-normal placeholder:text-gray-400" />
          </div>

          <div>
            <label class="block font-black mb-2 flex justify-between items-end text-sm uppercase">
              <span class="border-b-2 border-black inline-block">Description</span>
              <span class="text-[10px] text-gray-400 capitalize font-mono">Markdown: **bold**, *italic*, - list</span>
            </label>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
              <textarea
                v-model="form.description"
                rows="6"
                placeholder="Explain details about the project..."
                class="w-full p-4 border-2 border-black font-medium focus:bg-gray-50 focus:outline-none transition-colors shadow-[4px_4px_0px_0px_rgba(0,0,0,0)] focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] placeholder:font-normal placeholder:text-gray-400 resize-y"></textarea>

              <div class="border-2 border-black border-dashed p-3 bg-gray-50 overflow-y-auto max-h-[170px]">
                <div class="text-[10px] font-black uppercase text-gray-400 mb-2">Live Preview:</div>
                <div v-html="renderMarkdown(form.description)" class="markdown-preview font-mono text-sm"></div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block font-black mb-2 text-xs uppercase flex items-center gap-2">
                <Icon icon="lucide:calendar" class="text-lg" />
                Start Date
                <span class="text-black">*</span>
              </label>
              <input
                v-model="form.start_date"
                type="date"
                class="w-full p-3 border-2 border-black font-mono text-sm focus:bg-gray-50 focus:outline-none transition-colors shadow-[2px_2px_0px_0px_rgba(0,0,0,0)] focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]" />
            </div>
            <div>
              <label class="block font-black mb-2 text-xs uppercase flex items-center gap-2">
                <Icon icon="lucide:calendar-check" class="text-lg" />
                End Date
                <span class="text-black">*</span>
              </label>
              <input
                v-model="form.end_date"
                type="date"
                class="w-full p-3 border-2 border-black font-mono text-sm focus:bg-gray-50 focus:outline-none transition-colors shadow-[2px_2px_0px_0px_rgba(0,0,0,0)] focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]" />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="relative" ref="dropdownStatusRef">
              <label class="block font-black mb-2 text-xs uppercase flex items-center gap-2">
                <Icon icon="lucide:activity" class="text-lg" />
                Status
                <span class="text-black">*</span>
              </label>
              <div class="relative z-20">
                <button
                  type="button"
                  @click.stop="isStatusDropdownOpen = !isStatusDropdownOpen"
                  class="w-full font-mono bg-white flex justify-between items-center focus:outline-none transition-all text-left text-sm"
                  :class="[ 'border-2 border-black px-3', isStatusDropdownOpen ? 'border-b-0 pb-[14px] pt-3 bg-white shadow-none' : 'py-3 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]', ]">
                  <span class="truncate">{{ statusOptions.find(o => o.value === form.status)?.label || 'Select Status' }}</span>
                  <Icon
                    icon="lucide:chevron-down"
                    class="transition-transform duration-200"
                    :class="isStatusDropdownOpen ? 'rotate-180' : ''" />
                </button>

                <div
                  v-if="isStatusDropdownOpen"
                  class="absolute top-full left-0 w-full bg-white border-2 border-t-0 border-black shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] max-h-60 overflow-y-auto">
                  <div
                    v-for="opt in statusOptions"
                    :key="opt.value"
                    @click="selectStatus(opt.value)"
                    class="p-3 border-b-2 border-black last:border-b-0 cursor-pointer text-sm hover:bg-black hover:text-white transition-colors flex items-center justify-between group font-bold"
                    :class="form.status === opt.value ? 'bg-gray-200' : ''">
                    <span>{{ opt.label }}</span>
                    <Icon v-if="form.status === opt.value" icon="lucide:check" class="group-hover:text-black text-black" />
                  </div>
                </div>
              </div>
            </div>

            <div class="relative" ref="dropdownTypeRef">
              <label class="block font-black mb-2 text-xs uppercase flex items-center gap-2">
                <Icon icon="lucide:folder-type" class="text-lg" />
                Type
                <span class="text-gray-400 text-[10px] normal-case ml-1">(optional)</span>
              </label>
              <div class="relative z-10">
                <button
                  type="button"
                  @click.stop="isTypeDropdownOpen = !isTypeDropdownOpen"
                  class="w-full font-mono bg-white flex justify-between items-center focus:outline-none transition-all text-left text-sm"
                  :class="[ 'border-2 border-black px-3', isTypeDropdownOpen ? 'border-b-0 pb-[14px] pt-3 bg-white shadow-none' : 'py-3 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]', ]">
                  <span class="truncate">{{ form.type ? typeOptions.find(o => o.value === form.type)?.label : '— No Type —' }}</span>
                  <Icon
                    icon="lucide:chevron-down"
                    class="transition-transform duration-200"
                    :class="isTypeDropdownOpen ? 'rotate-180' : ''" />
                </button>

                <div
                  v-if="isTypeDropdownOpen"
                  class="absolute top-full left-0 w-full bg-white border-2 border-t-0 border-black shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] max-h-60 overflow-y-auto">
                  <div
                    @click="selectType('')"
                    class="p-3 border-b-2 border-black last:border-b-0 cursor-pointer text-sm hover:bg-black hover:text-white transition-colors flex items-center justify-between group font-bold"
                    :class="form.type === '' ? 'bg-gray-200' : ''">
                    <span>— No Type —</span>
                    <Icon v-if="form.type === ''" icon="lucide:check" class="group-hover:text-black text-black" />
                  </div>
                  <div
                    v-for="opt in typeOptions"
                    :key="opt.value"
                    @click="selectType(opt.value)"
                    class="p-3 border-b-2 border-black last:border-b-0 cursor-pointer text-sm hover:bg-black hover:text-white transition-colors flex items-center justify-between group font-bold"
                    :class="form.type === opt.value ? 'bg-gray-200' : ''">
                    <span>{{ opt.label }}</span>
                    <Icon v-if="form.type === opt.value" icon="lucide:check" class="group-hover:text-black text-black" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block font-black mb-2 text-xs uppercase flex items-center gap-2">
                <Icon icon="mdi:github" class="text-lg" />
                Repository
              </label>
              <input
                v-model="form.repository_link"
                type="url"
                placeholder="https://github.com/..."
                class="w-full p-3 border-2 border-black font-mono text-sm focus:bg-gray-50 focus:outline-none transition-colors shadow-[2px_2px_0px_0px_rgba(0,0,0,0)] focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]" />
            </div>
            <div>
              <label class="block font-black mb-2 text-xs uppercase flex items-center gap-2">
                <Icon icon="mdi:web" class="text-lg" />
                Live Demo
              </label>
              <input
                v-model="form.live_demo_link"
                type="url"
                placeholder="https://mysite.com"
                class="w-full p-3 border-2 border-black font-mono text-sm focus:bg-gray-50 focus:outline-none transition-colors shadow-[2px_2px_0px_0px_rgba(0,0,0,0)] focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]" />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block font-black mb-2 text-xs uppercase flex items-center gap-2">
                <Icon icon="lucide:calculator" class="text-lg" />
                Team Size
                <span class="text-gray-400 text-[10px] normal-case ml-1">(total people)</span>
              </label>
              <input
                v-model="form.team_size"
                type="number"
                min="1"
                placeholder="e.g. 5"
                class="w-full p-3 border-2 border-black font-mono text-sm focus:bg-gray-50 focus:outline-none transition-colors shadow-[2px_2px_0px_0px_rgba(0,0,0,0)] focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]" />
            </div>
            <div>
              <label class="block font-black mb-2 text-xs uppercase flex items-center gap-2">
                <Icon icon="lucide:user-cog" class="text-lg" />
                Your Role
                <span class="text-gray-400 text-[10px] normal-case ml-1">(e.g. Fullstack)</span>
              </label>
              <input
                v-model="form.role"
                type="text"
                placeholder="e.g. Lead Developer"
                class="w-full p-3 border-2 border-black font-mono text-sm focus:bg-gray-50 focus:outline-none transition-colors shadow-[2px_2px_0px_0px_rgba(0,0,0,0)] focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]" />
            </div>
          </div>

          <div
            class="border-2 border-black bg-gray-50 p-4 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] flex flex-col sm:flex-row items-start sm:items-center gap-4 transition-transform hover:-translate-y-1">
            <div class="relative flex items-center">
              <input
                type="checkbox"
                id="is_featured"
                v-model="form.is_featured"
                class="peer h-6 w-6 cursor-pointer appearance-none border-2 border-black bg-white transition-all checked:bg-black checked:bg-[url('https://api.iconify.design/lucide/check.svg?color=white')] checked:bg-center checked:bg-no-repeat" />
              <label for="is_featured" class="ml-3 font-black uppercase cursor-pointer select-none text-lg">
                Feature Project?
              </label>
            </div>
            <div class="text-xs font-mono text-gray-500 border-l-2 border-black pl-4 hidden sm:block">
              Pinned to Homepage
              <br />
              Hero Section.
            </div>
          </div>
          <div class="pt-2">
            <label class="block font-black mb-3 border-b-2 border-black inline-block text-sm uppercase">
              Tech Stack
              <span class="text-black">*</span>
            </label>

            <div
              v-if="isFetchingSkills"
              class="p-4 border-2 border-black border-dashed bg-gray-50 text-center font-mono animate-pulse">
              LOADING TECH DATA...
            </div>

            <div v-else class="flex flex-wrap gap-3">
              <button
                v-for="skill in skills"
                :key="skill.id"
                type="button"
                @click="toggleSkill(skill.id)"
                class="group relative px-4 py-2 text-sm font-bold border-2 border-black transition-all duration-200 select-none flex items-center gap-2 shadow-[3px_3px_0px_0px_rgba(0,0,0,1)] hover:shadow-[5px_5px_0px_0px_rgba(0,0,0,1)] hover:-translate-y-0.5 active:translate-y-0 active:shadow-[0px_0px_0px_0px_rgba(0,0,0,1)]"
                :class="selectedSkillIds.includes(skill.id) ? 'bg-black text-white hover:bg-gray-800' : 'bg-white text-black hover:bg-green-50'">
                <Icon
                  :icon="skill.identifier || 'lucide:code'"
                  class="text-lg"
                  :class="selectedSkillIds.includes(skill.id) ? 'text-white' : 'text-gray-700 group-hover:text-black'" />
                <span class="font-mono uppercase">{{ skill.name }}</span>
                <div
                  v-if="selectedSkillIds.includes(skill.id)"
                  class="absolute -top-2 -right-2 bg-black text-white hover:text-black hover:bg-gray-100 border-2 border-black w-5 h-5 flex items-center justify-center rounded-full text-xs">
                  <Icon icon="lucide:check" stroke-width="4" />
                </div>
              </button>
            </div>
            <p class="font-mono text-xs text-gray-500 mt-3 text-right">{{ selectedSkillIds.length }} tech selected</p>
          </div>
        </div>

        <div class="w-full lg:w-[350px] flex flex-col gap-6 flex-shrink-0">
          <div>
            <label class="block font-black mb-2 border-b-2 border-black inline-block text-sm uppercase">
              Thumbnail
              <span v-if="!isEditMode" class="text-black">*</span>
              <span v-else class="text-gray-400 text-xs normal-case ml-2">(Biarkan kosong jika tidak diganti)</span>
            </label>
            <div
              class="relative w-full aspect-[4/3] border-4 border-black bg-gray-100 flex flex-col items-center justify-center cursor-pointer hover:bg-white transition-colors group shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]"
              @click="$refs.fileInput.click()">
              <input
                id="file-upload"
                ref="fileInput"
                type="file"
                class="hidden"
                accept="image/*"
                @change="handleFileChange" />

              <div v-if="previewImage" class="relative w-full h-full p-2 bg-white">
                <img loading="lazy" :src="previewImage" class="w-full h-full object-cover border-2 border-black" />
                <button
                  @click.stop="removeImage"
                  class="absolute top-0 right-0 bg-white text-black hover:bg-black hover:text-white p-2 border-2 border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:scale-110 transition-transform z-10"
                  title="Remove Image">
                  <Icon icon="lucide:trash-2" />
                </button>
              </div>

              <div v-else class="text-center p-6 space-y-3">
                <div
                  class="bg-white p-4 inline-block border-2 border-black shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] group-hover:scale-110 transition-transform duration-300">
                  <Icon icon="lucide:image-plus" class="text-4xl text-black" />
                </div>
                <div>
                  <h4 class="font-black text-lg uppercase">{{ isEditMode ? "Change Cover" : "Upload Cover" }}</h4>
                  <p class="text-xs font-mono text-gray-500">Max 2MB (JPG/PNG)</p>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-auto pt-6 border-t-4 border-black border-dashed flex flex-col gap-3">
            <button
              type="submit"
              :disabled="isLoading"
              class="w-full py-4 bg-black text-white hover:text-black hover:bg-gray-100 border-2 border-black font-black text-xl shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:bg-gray-800 hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] active:shadow-none active:translate-x-[4px] active:translate-y-[4px] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3 uppercase italic">
              <template v-if="isLoading">
                <Icon icon="svg-spinners:3-dots-fade" class="text-2xl" />
                <span>{{ isEditMode ? "Updating..." : "Uploading..." }}</span>
              </template>
              <template v-else>
                <span>{{ isEditMode ? "Update Project" : "Launch Project" }}</span>
                <Icon icon="lucide:rocket" />
              </template>
            </button>

            <router-link
              to="/admin/dashboard/projects"
              class="w-full py-3 bg-white text-black border-2 border-black font-bold text-lg shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:bg-gray-100 hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] active:shadow-none active:translate-x-[4px] active:translate-y-[4px] transition-all flex items-center justify-center gap-2 uppercase">
              Cancel
            </router-link>
          </div>
        </div>
      </form>
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
