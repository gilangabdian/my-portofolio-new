<script setup>
import { ref, onMounted } from "vue";
import Swal from "sweetalert2";
import { Icon } from "@iconify/vue";
import { getAllArtworks, storeArtwork, deleteArtwork as deleteArtworkApi } from "../../../../lib/api/ArtworkApi";

const artworks = ref([]);
const isLoading = ref(false);

const fileInput = ref(null);
const previewImages = ref([]);
const selectedFiles = ref([]);
const isUploading = ref(false);
const uploadProgressText = ref("");


const fetchArtworks = async () => {
  isLoading.value = true;
  try {
    const response = await getAllArtworks();
    const data = await response.json();
    artworks.value = data;
  } catch (error) {
    Swal.fire("Error", "Gagal mengambil data artworks", "error");
  } finally {
    isLoading.value = false;
  }
};

const handleFileChange = (e) => {
  const files = Array.from(e.target.files);
  if (files.length > 0) {
    const newFiles = files.filter(
      (newFile) => !selectedFiles.value.some((existingFile) => existingFile.name === newFile.name && existingFile.size === newFile.size)
    );
    
    if (newFiles.length > 0) {
      selectedFiles.value = [...selectedFiles.value, ...newFiles];
      const newPreviews = newFiles.map(file => URL.createObjectURL(file));
      previewImages.value = [...previewImages.value, ...newPreviews];
    }
  }
  
  if (fileInput.value) fileInput.value.value = "";
};

const removeSelectedFile = (index) => {
  URL.revokeObjectURL(previewImages.value[index]);
  previewImages.value.splice(index, 1);
  selectedFiles.value.splice(index, 1);
};

const uploadArtwork = async () => {
  if (selectedFiles.value.length === 0) {
    Swal.fire("Peringatan", "Pilih gambar terlebih dahulu", "warning");
    return;
  }

  isUploading.value = true;
  const totalFiles = selectedFiles.value.length;
  let successCount = 0;

  try {
    for (let i = 0; i < totalFiles; i++) {
      uploadProgressText.value = `Mengunggah ${i + 1} dari ${totalFiles}... (${Math.round((i / totalFiles) * 100)}%)`;
      
      const formData = new FormData();
      formData.append("image", selectedFiles.value[i]);

      const response = await storeArtwork(formData);
      if (response.ok) {
        successCount++;
      } else {
        console.error("Failed to upload " + selectedFiles.value[i].name);
      }
    }

    uploadProgressText.value = `Selesai! 100%`;

    Swal.fire({
      icon: "success",
      title: "Berhasil",
      text: `${successCount} Artworks berhasil diupload!`,
      timer: 1500,
      showConfirmButton: false,
    });

    resetForm();
    fetchArtworks();
  } catch (error) {
    Swal.fire("Error", "Terjadi kesalahan saat mengupload", "error");
  } finally {
    isUploading.value = false;
    uploadProgressText.value = "";
  }
};

const deleteArtwork = async (id) => {
  const result = await Swal.fire({
    title: "Yakin Hapus?",
    text: "Artwork ini akan dihapus permanen!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#000",
    cancelButtonColor: "#d33",
    confirmButtonText: "Ya, Hapus!",
    customClass: { popup: "border-4 border-black rounded-none" },
  });

  if (result.isConfirmed) {
    try {
      const response = await deleteArtworkApi(id);

      if (!response.ok) throw new Error("Delete failed");

      Swal.fire({
        icon: "success",
        title: "Terhapus!",
        text: "Artwork berhasil dihapus.",
        timer: 1500,
        showConfirmButton: false,
      });
      fetchArtworks();
    } catch (error) {
      Swal.fire("Error", "Gagal menghapus artwork", "error");
    }
  }
};

const resetForm = () => {
  selectedFiles.value = [];
  previewImages.value.forEach(url => URL.revokeObjectURL(url));
  previewImages.value = [];
  if (fileInput.value) fileInput.value.value = "";
};

onMounted(() => {
  fetchArtworks();
});
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center border-b-4 border-black pb-4">
      <h1 class="text-3xl font-black tracking-tighter uppercase">Artworks</h1>
    </div>

    <!-- Upload Section -->
    <div class="border-4 border-black bg-white p-6 shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
      <h2 class="text-xl font-bold font-mono border-b-2 border-black pb-2 mb-4">Upload New Artwork</h2>
      <div class="flex flex-col md:flex-row gap-6 items-start">
        <div class="w-full md:w-1/2">
          <label class="block font-bold font-mono mb-2">Select Image</label>
          <input
            type="file"
            ref="fileInput"
            @change="handleFileChange"
            accept="image/*"
            multiple
            class="block w-full border-4 border-black bg-white p-2 text-sm font-mono cursor-pointer file:mr-4 file:py-2 file:px-4 file:border-0 file:text-sm file:font-semibold file:bg-black file:text-white hover:file:bg-gray-800"
          />
          <button
            @click="uploadArtwork"
            :disabled="isUploading || selectedFiles.length === 0"
            class="mt-4 px-6 py-2 bg-black text-white font-bold uppercase tracking-wider border-4 border-black hover:bg-white hover:text-black transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ isUploading ? (uploadProgressText || 'Uploading...') : 'Upload' }}
          </button>
        </div>

        <div class="w-full md:w-1/2" v-if="previewImages.length > 0">
          <label class="block font-bold font-mono mb-2">Preview ({{ previewImages.length }} selected)</label>
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 border-4 border-black bg-gray-100 p-4 max-h-64 overflow-y-auto">
            <div v-for="(preview, index) in previewImages" :key="index" class="relative group">
              <img :src="preview" class="w-full h-24 object-cover border-2 border-black" />
              <button 
                @click="removeSelectedFile(index)" 
                :disabled="isUploading"
                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 border-2 border-black hover:bg-red-600 hover:scale-110 transition-transform disabled:opacity-50 disabled:cursor-not-allowed z-10 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]"
                title="Batalkan gambar ini"
              >
                <Icon icon="lucide:x" class="text-sm font-bold" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Gallery Management -->
    <div v-if="isLoading" class="text-center p-8 font-mono font-bold">Loading...</div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <div
        v-for="artwork in artworks"
        :key="artwork.id"
        class="border-4 border-black bg-gray-50 group relative flex items-center justify-center h-48 overflow-hidden"
      >
        <img :src="artwork.image_url" loading="lazy" class="max-w-full max-h-full object-contain" />
        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
          <button
            @click="deleteArtwork(artwork.id)"
            class="bg-red-500 border-4 border-black text-white p-2 hover:bg-red-600 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] transition-all"
            title="Delete Artwork"
          >
            <Icon icon="lucide:trash-2" width="20" height="20" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
