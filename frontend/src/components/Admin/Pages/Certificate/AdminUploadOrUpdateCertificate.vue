<script setup>
import { useLocalStorage } from "@vueuse/core";
import { reactive, ref, onMounted, computed, onBeforeUnmount } from "vue";
import { useRoute, useRouter } from "vue-router";
import { Icon } from "@iconify/vue";
import { adminUploadCertificate, adminUpdateCertificate, getSingleCertificate } from "../../../../lib/api/CertificateApi";
import { alertSuccess, alertError } from "../../../../lib/alert";
import { marked } from "marked";

const route = useRoute();
const router = useRouter();
const token = useLocalStorage("token", "");

// Deteksi Mode (Create / Edit)
const certId = route.params.id;
const isEditMode = computed(() => !!certId);

const isLoading = ref(false);
const file = ref(null);
const previewImage = ref(null);

// Options untuk dropdown type
const typeOptions = [
  { value: 'course', label: 'Course' },
  { value: 'seminar', label: 'Seminar' },
  { value: 'webinar', label: 'Webinar' },
  { value: 'workshop', label: 'Workshop' },
  { value: 'bootcamp', label: 'Bootcamp' },
  { value: 'competition', label: 'Competition' },
];

const form = reactive({
  title: "",
  issuer: "",
  credential_link: "",
  description: "",
  is_featured: false,
  start_date: "",
  end_date: "",
  type: "",
});
const renderMarkdown = (text) => {
  if (!text) return "";
  return marked.parse(text, { breaks: true });
};

// --- CUSTOM DROPDOWN STATE ---
const isTypeDropdownOpen = ref(false);
const dropdownTypeRef = ref(null);

const selectType = (value) => {
  form.type = value;
  isTypeDropdownOpen.value = false;
};

const handleClickOutside = (event) => {
  if (isTypeDropdownOpen.value && dropdownTypeRef.value && !dropdownTypeRef.value.contains(event.target)) {
    isTypeDropdownOpen.value = false;
  }
};

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
});

onMounted(async () => {
  document.addEventListener("click", handleClickOutside);

  if (isEditMode.value) {
    isLoading.value = true;
    try {
      const response = await getSingleCertificate(certId);
      const result = await response.json();
      console.log(result);
      const data = result.data || result;

      form.title = data.title;
      form.issuer = data.issuer;
      form.credential_link = data.credential_link;
      form.description = data.description;
      form.is_featured = data.is_featured ? true : false;
      // Load kolom baru
      form.start_date = data.start_date ? data.start_date.substring(0, 10) : "";
      form.end_date = data.end_date ? data.end_date.substring(0, 10) : "";
      form.type = data.type || "";

      if (data.image_path) {
        previewImage.value = data.image_url;
      }
    } catch (error) {
      console.error(error);
      alertError("Gagal mengambil data sertifikat.");
      router.push("/admin/dashboard/certificates");
    } finally {
      isLoading.value = false;
    }
  }
});

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

const handleSubmit = async () => {
  // Validasi Sederhana
  if (!form.title || !form.issuer) {
    alertError("Judul dan Penerbit (Issuer) wajib diisi!");
    return;
  }
  if (!isEditMode.value && !file.value) {
    alertError("Gambar sertifikat wajib diupload!");
    return;
  }

  isLoading.value = true;
  try {
    const formData = new FormData();
    formData.append("title", form.title);
    formData.append("issuer", form.issuer);
    formData.append("description", form.description || "");
    formData.append("credential_link", form.credential_link || "");
    formData.append("is_featured", form.is_featured ? "1" : "0");
    // Append kolom baru
    formData.append("start_date", form.start_date);
    formData.append("end_date", form.end_date);
    if (form.type) {
      formData.append("type", form.type);
    }

    if (file.value) {
      formData.append("image", file.value);
    }

    let response;
    if (isEditMode.value) {
      // TRICK UNTUK LARAVEL: Method Spoofing
      // Kirim sebagai POST, tapi kasih tahu backend ini sebenarnya PUT
      formData.append("_method", "PUT");
      response = await adminUpdateCertificate(token.value, certId, formData);
    } else {
      response = await adminUploadCertificate(token.value, formData);
    }

    const responseBody = await response.json();

    if (response.ok || response.status === 201 || response.status === 200) {
      await alertSuccess(isEditMode.value ? "Sertifikat berhasil diupdate!" : "Sertifikat berhasil ditambahkan!");
      router.push("/admin/dashboard/certificates");
    } else {
      await alertError(responseBody.message || "Gagal menyimpan data.");
    }
  } catch (e) {
    console.error(e);
    alertError("Terjadi kesalahan sistem.");
  } finally {
    isLoading.value = false;
  }
};
</script>

<template>
  <div class="p-6 max-w-7xl mx-auto">
    <div class="mb-8">
      <router-link
        to="/admin/dashboard/certificates"
        class="inline-flex items-center gap-2 font-bold font-mono text-sm mb-4 hover:underline hover:text-gray-500 transition-colors">
        <Icon icon="lucide:arrow-left" class="text-lg" />
        BACK TO LIST
      </router-link>

      <div class="border-b-4 border-black pb-4 flex justify-between items-end">
        <div>
          <h1 class="text-3xl md:text-4xl font-black italic uppercase">
            {{ isEditMode ? "EDIT CERTIFICATE" : "UPLOAD CERTIFICATE" }}
          </h1>
          <p class="font-mono text-gray-600 mt-2 text-sm md:text-base">
            {{ isEditMode ? "Update your achievement details." : "Add a new milestone to your portfolio." }}
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
              Certificate Title
              <span class="text-black">*</span>
            </label>
            <input
              v-model="form.title"
              type="text"
              placeholder="e.g. AWS Certified Solutions Architect"
              class="w-full p-4 border-2 border-black font-bold focus:bg-gray-50 focus:outline-none transition-colors shadow-[4px_4px_0px_0px_rgba(0,0,0,0)] focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]" />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block font-black mb-2 text-xs uppercase flex items-center gap-2">
                <Icon icon="lucide:building-2" class="text-lg" />
                Issuer (Penerbit)
                <span class="text-black">*</span>
              </label>
              <input
                v-model="form.issuer"
                type="text"
                placeholder="e.g. Google, Udemy, Dicoding"
                class="w-full p-3 border-2 border-black font-mono text-sm focus:bg-gray-50 focus:outline-none transition-colors shadow-[2px_2px_0px_0px_rgba(0,0,0,0)] focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]" />
            </div>
            <div>
              <label class="block font-black mb-2 text-xs uppercase flex items-center gap-2">
                <Icon icon="lucide:link" class="text-lg" />
                Credential Link
              </label>
              <input
                v-model="form.credential_link"
                type="url"
                placeholder="https://..."
                class="w-full p-3 border-2 border-black font-mono text-sm focus:bg-gray-50 focus:outline-none transition-colors shadow-[2px_2px_0px_0px_rgba(0,0,0,0)] focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]" />
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

          <div class="relative" ref="dropdownTypeRef">
            <label class="block font-black mb-2 text-xs uppercase flex items-center gap-2">
              <Icon icon="lucide:tag" class="text-lg" />
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

          <div
            class="border-2 border-black bg-gray-50 p-4 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] flex flex-col sm:flex-row items-start sm:items-center gap-4 transition-transform hover:-translate-y-1">
            <div class="relative flex items-center">
              <input
                type="checkbox"
                id="is_featured"
                v-model="form.is_featured"
                class="peer h-6 w-6 cursor-pointer appearance-none border-2 border-black bg-white transition-all checked:bg-black checked:bg-[url('https://api.iconify.design/lucide/check.svg?color=white')] checked:bg-center checked:bg-no-repeat" />
              <label for="is_featured" class="ml-3 font-black uppercase cursor-pointer select-none text-lg">
                Feature Certificate?
              </label>
            </div>
            <div class="text-xs font-mono text-gray-500 border-l-2 border-black pl-4 hidden sm:block">
              Pinned to Homepage
              <br />
              Hero Section.
            </div>
          </div>
          <div>
            <label class="block font-black mb-2 flex justify-between items-end text-sm uppercase">
              <span class="border-b-2 border-black inline-block">Description</span>
              <span class="text-[10px] text-gray-400 capitalize font-mono">Markdown: **bold**, *italic*, - list</span>
            </label>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
              <textarea
                v-model="form.description"
                rows="5"
                placeholder="Briefly describe what you learned or achieved..."
                class="w-full p-4 border-2 border-black font-medium focus:bg-gray-50 focus:outline-none transition-colors shadow-[4px_4px_0px_0px_rgba(0,0,0,0)] focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] resize-y"></textarea>

              <div class="border-2 border-black border-dashed p-3 bg-gray-50 overflow-y-auto max-h-[150px]">
                <div class="text-[10px] font-black uppercase text-gray-400 mb-2">Live Preview:</div>
                <div v-html="renderMarkdown(form.description)" class="markdown-preview font-mono text-sm"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="w-full lg:w-[350px] flex flex-col gap-6 flex-shrink-0">
          <div>
            <label class="block font-black mb-2 border-b-2 border-black inline-block text-sm uppercase">
              Certificate Image
              <span v-if="!isEditMode" class="text-black">*</span>
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
                <img loading="lazy" :src="previewImage" class="w-full h-full object-contain border-2 border-black" />
                <button
                  @click.stop="removeImage"
                  class="absolute top-0 right-0 bg-white text-black hover:bg-black hover:text-white p-2 border-2 border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:scale-110 transition-transform z-10"
                  type="button">
                  <Icon icon="lucide:trash-2" />
                </button>
              </div>

              <div v-else class="text-center p-6 space-y-3">
                <div
                  class="bg-white p-4 inline-block border-2 border-black shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] group-hover:scale-110 transition-transform duration-300">
                  <Icon icon="lucide:image-plus" class="text-4xl text-black" />
                </div>
                <div>
                  <h4 class="font-black text-lg uppercase">{{ isEditMode ? "Change Image" : "Upload Image" }}</h4>
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
                <span>Processing...</span>
              </template>
              <template v-else>
                <span>{{ isEditMode ? "Save Changes" : "Publish Certificate" }}</span>
                <Icon icon="lucide:check-circle" />
              </template>
            </button>

            <router-link
              to="/admin/dashboard/certificates"
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
