<script setup>
import { ref, onMounted, computed } from "vue";
import { useLocalStorage } from "@vueuse/core";
import { Icon } from "@iconify/vue";
import { getProfile, saveProfile } from "../../../../lib/api/ProfileApi";
import { alertSuccess, alertError } from "../../../../lib/alert";

// State
const isLoading = ref(true);
const isSubmitting = ref(false);
const token = useLocalStorage("token", "");

// Form Data Active
const form = ref({
  name: "",
  job_title: "",
  about_description: "",
});

// State untuk menyimpan data asli dari Server (untuk perbandingan)
const originalForm = ref({
  name: "",
  job_title: "",
  about_description: "",
});

// File Handling
const photoPreview = ref(null);
const photoFile = ref(null);

// --- NEW: Secondary Image Handling ---
const secondaryImagePreview = ref(null);
const secondaryImageFile = ref(null);
// -------------------------------------

const cvFile = ref(null);
const currentCvPath = ref(null);

// Refs inputs
const photoInputRef = ref(null);
const secondaryImageInputRef = ref(null); // --- NEW Ref ---
const cvInputRef = ref(null);

// Computed Property: Deteksi perubahan
const hasChanges = computed(() => {
  // Cek apakah ada file baru (Photo Utama ATAU Secondary Image ATAU CV)
  const hasNewFiles =
    photoFile.value !== null ||
    secondaryImageFile.value !== null || // --- NEW Check ---
    cvFile.value !== null;

  const hasTextChanges =
    form.value.name !== originalForm.value.name ||
    form.value.job_title !== originalForm.value.job_title ||
    form.value.about_description !== originalForm.value.about_description;

  return hasNewFiles || hasTextChanges;
});

// Fetch Data
const fetchData = async () => {
  try {
    isLoading.value = true;
    const response = await getProfile();
    const result = await response.json();
    console.log(result);

    if (result.about) {
      form.value.name = result.about.name;
      form.value.job_title = result.about.job_title;
      form.value.about_description = result.about.about_description; // Perhatikan field ini harus sesuai DB

      originalForm.value = {
        name: result.about.name,
        job_title: result.about.job_title,
        about_description: result.about.about_description,
      };

      if (result.about.photo_url) {
        photoPreview.value = result.about.photo_url;
      }

      // --- NEW: Set Preview Secondary Image ---
      if (result.about.secondary_image_url) {
        secondaryImagePreview.value = result.about.secondary_image_url;
      }
      // ----------------------------------------

      if (result.about.cv_url) {
        currentCvPath.value = result.about.cv_url;
      }
    }
  } catch (error) {
    console.error("Error fetching profile:", error);
  } finally {
    isLoading.value = false;
  }
};

const handlePhotoChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
  }
};

// --- NEW: Handle Secondary Image Change ---
const handleSecondaryImageChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    secondaryImageFile.value = file;
    secondaryImagePreview.value = URL.createObjectURL(file);
  }
};
// ------------------------------------------

const handleCvChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    cvFile.value = file;
  }
};

const handleCancel = () => {
  photoFile.value = null;
  secondaryImageFile.value = null; // Reset Secondary
  cvFile.value = null;

  if (photoInputRef.value) photoInputRef.value.value = "";
  if (secondaryImageInputRef.value) secondaryImageInputRef.value.value = ""; // Reset Input Secondary
  if (cvInputRef.value) cvInputRef.value.value = "";

  form.value = { ...originalForm.value };
  fetchData(); // Fetch ulang untuk reset preview gambar ke URL server
};

const handleSubmit = async () => {
  isSubmitting.value = true;
  try {
    const formData = new FormData();
    formData.append("name", form.value.name);
    formData.append("job_title", form.value.job_title);
    formData.append("about_description", form.value.about_description);

    if (photoFile.value) formData.append("photo_path", photoFile.value);

    // --- NEW: Append Secondary Image ---
    if (secondaryImageFile.value) formData.append("secondary_image", secondaryImageFile.value);
    // -----------------------------------

    if (cvFile.value) formData.append("cv", cvFile.value);

    const response = await saveProfile(token.value, formData);

    if (response.ok) {
      await alertSuccess("Profile berhasil diupdate!");

      // Reset inputs
      if (photoInputRef.value) photoInputRef.value.value = "";
      if (secondaryImageInputRef.value) secondaryImageInputRef.value.value = "";
      if (cvInputRef.value) cvInputRef.value.value = "";

      photoFile.value = null;
      secondaryImageFile.value = null;
      cvFile.value = null;

      fetchData();
    } else {
      const result = await response.json();
      await alertError(result.message || "Gagal menyimpan profile.");
    }
  } catch (error) {
    console.error(error);
    alertError("Terjadi kesalahan sistem.");
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(() => {
  fetchData();
});
</script>

<template>
  <div class="p-6 max-w-5xl mx-auto">
    <div class="mb-10 border-b-4 border-black pb-4">
      <h1 class="text-3xl md:text-4xl font-black italic uppercase">MY PROFILE</h1>
      <p class="font-mono text-gray-600 mt-2">Introduce yourself to the world.</p>
    </div>

    <div v-if="isLoading" class="p-8 text-center font-mono animate-pulse border-4 border-black bg-white">
      LOADING PROFILE DATA...
    </div>

    <div v-else class="border-4 border-black bg-white shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] p-6 md:p-8 relative">
      <div class="absolute top-2 left-2 w-3 h-3 border-2 border-black rounded-full bg-gray-300"></div>
      <div class="absolute top-2 right-2 w-3 h-3 border-2 border-black rounded-full bg-gray-300"></div>
      <div class="absolute bottom-2 left-2 w-3 h-3 border-2 border-black rounded-full bg-gray-300"></div>
      <div class="absolute bottom-2 right-2 w-3 h-3 border-2 border-black rounded-full bg-gray-300"></div>

      <form @submit.prevent="handleSubmit" class="space-y-6 mt-2">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="md:col-span-1 flex flex-col gap-8">
            <div class="flex flex-col gap-4">
              <label class="font-bold uppercase border-b-2 border-black inline-block w-max">Profile Picture</label>
              <div class="relative group">
                <div
                  class="w-full border-4 border-black bg-gray-100 flex items-center justify-center overflow-hidden shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
                  <img
                    v-if="photoPreview"
                    loading="lazy"
                    :src="photoPreview"
                    class="w-full h-auto"
                    alt="Profile Preview" />
                  <div v-else class="text-gray-400 flex flex-col items-center">
                    <Icon icon="lucide:image" class="w-12 h-12 mb-2" />
                    <span class="font-mono text-xs uppercase">No Image</span>
                  </div>
                </div>
                <label
                  class="absolute bottom-2 right-2 bg-white border-2 border-black p-2 cursor-pointer hover:scale-110 transition-transform shadow-sm"
                  title="Change Photo">
                  <Icon icon="lucide:camera" class="w-5 h-5" />
                  <input ref="photoInputRef" type="file" @change="handlePhotoChange" accept="image/*" class="hidden" />
                </label>
              </div>
              <p class="text-xs font-mono text-gray-500 text-center">*Main Photo (JPG/PNG)</p>
            </div>

            <div class="flex flex-col gap-4">
              <label class="font-bold uppercase border-b-2 border-black inline-block w-max">Hero Swap Image</label>
              <div class="relative group">
                <div
                  class="w-full aspect-[4/5] border-4 border-black bg-gray-100 flex items-center justify-center overflow-hidden shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
                  <img
                    v-if="secondaryImagePreview"
                    :src="secondaryImagePreview"
                    loading="lazy"
                    class="w-full h-full object-cover"
                    alt="Secondary Preview" />
                  <div v-else class="text-gray-400 flex flex-col items-center">
                    <Icon icon="lucide:image-plus" class="w-12 h-12 mb-2" />
                    <span class="font-mono text-xs uppercase">No Swap Image</span>
                  </div>
                </div>
                <label
                  class="absolute bottom-2 right-2 bg-gray-100 border-2 border-black p-2 cursor-pointer hover:scale-110 transition-transform shadow-sm"
                  title="Change Secondary Photo">
                  <Icon icon="lucide:camera" class="w-5 h-5" />
                  <input
                    ref="secondaryImageInputRef"
                    type="file"
                    @change="handleSecondaryImageChange"
                    accept="image/*"
                    class="hidden" />
                </label>
              </div>
              <p class="text-xs font-mono text-gray-500 text-center">*Shown on Click/Hover (Optional)</p>
            </div>
          </div>

          <div class="md:col-span-2 space-y-5">
            <div>
              <label class="block font-bold uppercase mb-2">Full Name</label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full border-2 border-black p-3 font-mono focus:outline-none focus:bg-gray-50 focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all placeholder:text-gray-400"
                placeholder="Ex: Gilang Ages" />
            </div>
            <div>
              <label class="block font-bold uppercase mb-2">Job Title / Role</label>
              <input
                v-model="form.job_title"
                type="text"
                required
                class="w-full border-2 border-black p-3 font-mono focus:outline-none focus:bg-gray-50 focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all placeholder:text-gray-400"
                placeholder="Ex: Full Stack Developer" />
            </div>
            <div>
              <label class="block font-bold uppercase mb-2">About Description</label>
              <textarea
                v-model="form.about_description"
                rows="5"
                required
                class="w-full border-2 border-black p-3 font-mono focus:outline-none focus:bg-gray-50 focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all placeholder:text-gray-400 resize-none"
                placeholder="Tell the world who you are..."></textarea>
            </div>
            <div class="bg-gray-50 border-2 border-black p-4 border-dashed">
              <label class="block font-bold uppercase mb-2 flex items-center gap-2">
                <Icon icon="lucide:file-text" />
                Curriculum Vitae (PDF)
              </label>
              <div class="flex items-center gap-4">
                <input
                  ref="cvInputRef"
                  type="file"
                  @change="handleCvChange"
                  accept=".pdf"
                  class="block w-full text-sm font-mono file:mr-4 file:py-2 file:px-4 file:border-2 file:border-black file:text-sm file:font-bold file:uppercase file:bg-white file:text-black hover:file:bg-black hover:file:text-white cursor-pointer" />
              </div>
              <div v-if="currentCvPath && !cvFile" class="mt-2 text-xs font-mono">
                Current CV:
                <a
                  :href="currentCvPath"
                  target="_blank"
                  class="text-black underline underline font-bold hover:text-black">
                  View PDF
                </a>
              </div>
            </div>
          </div>
        </div>

        <div class="border-t-2 border-black pt-6 flex flex-col md:flex-row justify-end gap-3 md:gap-4">
          <button
            v-if="hasChanges"
            type="button"
            @click="handleCancel"
            :disabled="isSubmitting"
            class="w-full md:w-auto justify-center bg-white text-black hover:bg-black hover:text-white border-2 border-black px-4 py-2 md:px-6 md:py-3 font-bold text-sm md:text-lg uppercase shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:translate-y-[2px] hover:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] active:translate-y-[4px] active:shadow-none transition-all flex items-center gap-2 disabled:opacity-50">
            <Icon icon="lucide:x" class="w-4 h-4 md:w-5 md:h-5" />
            <span>Cancel</span>
          </button>

          <button
            type="submit"
            :disabled="!hasChanges || isSubmitting"
            :class="[
              !hasChanges || isSubmitting
                ? 'bg-gray-200 text-gray-400 border-2 border-gray-300 cursor-not-allowed'
                : 'bg-black text-white hover:text-black hover:bg-gray-100 border-2 border-black shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:translate-y-[2px] hover:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] active:translate-y-[4px] active:shadow-none',
            ]"
            class="w-full md:w-auto justify-center px-4 py-2 md:px-8 md:py-3 font-black text-sm md:text-lg uppercase transition-all flex items-center gap-2">
            <Icon v-if="isSubmitting" icon="lucide:loader-2" class="animate-spin w-4 h-4 md:w-5 md:h-5" />
            <span v-else>{{ hasChanges ? "Save Changes" : "No Changes" }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
