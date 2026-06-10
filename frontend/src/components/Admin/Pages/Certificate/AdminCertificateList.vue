<script setup>
import { ref, onMounted } from "vue";
import { useLocalStorage } from "@vueuse/core";
import { Icon } from "@iconify/vue";
// TAMBAHAN: Import adminUpdateCertificate
import { getAllCertificates, adminDeleteCertificate, adminUpdateCertificate } from "../../../../lib/api/CertificateApi";
import { alertSuccess, alertError, alertConfirm } from "../../../../lib/alert";
import { marked } from "marked";

const certificates = ref([]);
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

const fetchData = async () => {
  isLoading.value = true;
  try {
    const response = await getAllCertificates();
    const result = await response.json();
    console.log(result);
    certificates.value = result.data || result;
  } catch (error) {
    console.error(error);
    alertError("Gagal mengambil data certificates.");
  } finally {
    isLoading.value = false;
  }
};

const handleDelete = async (id) => {
  if (!(await alertConfirm("Yakin ingin menghapus sertifikat ini? Data tidak bisa dikembalikan."))) {
    return;
  }

  try {
    const response = await adminDeleteCertificate(token.value, id);
    if (response.ok) {
      await alertSuccess("Sertifikat berhasil dihapus!");
      fetchData();
    } else {
      await alertError("Gagal menghapus data.");
    }
  } catch (error) {
    alertError("Terjadi kesalahan sistem.");
  }
};

// --- FITUR BARU: Toggle Featured ---
const handleToggleFeatured = async (cert) => {
  // 1. Simpan status lama
  const oldStatus = cert.is_featured;

  // 2. Optimistic Update (Ubah UI duluan)
  cert.is_featured = !oldStatus;

  try {
    // 3. Siapkan FormData (Backend butuh FormData + _method: PUT)
    const formData = new FormData();
    formData.append("_method", "PUT");
    formData.append("title", cert.title);
    formData.append("issuer", cert.issuer);
    formData.append("description", cert.description || "");
    formData.append("is_featured", cert.is_featured ? "1" : "0");
    // Required fields by backend validation
    formData.append("start_date", cert.start_date ? cert.start_date.substring(0, 10) : "");
    formData.append("end_date", cert.end_date ? cert.end_date.substring(0, 10) : "");

    // Panggil API Update
    const response = await adminUpdateCertificate(token.value, cert.id, formData);

    if (!response.ok) {
      throw new Error("Gagal update");
    }
  } catch (error) {
    console.error(error);
    // Rollback jika gagal
    cert.is_featured = oldStatus;
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
        <h1 class="text-3xl md:text-4xl font-black italic uppercase">MANAGE CERTIFICATES</h1>
        <p class="font-mono text-gray-600 mt-2">Showcase your achievements and credentials.</p>
      </div>

      <router-link v-if="!isLoading && certificates.length > 0" to="/admin/dashboard/certificates/create"
        class="bg-black text-white hover:text-black hover:bg-gray-100 border-2 border-black px-4 py-2 font-bold uppercase shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:translate-y-[2px] hover:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] active:translate-y-[4px] active:shadow-none transition-all flex items-center gap-2">
        <Icon icon="lucide:plus" class="text-xl" />
        <span>Add New</span>
      </router-link>
    </div>

    <div v-if="isLoading" class="p-8 text-center font-mono animate-pulse border-4 border-black bg-white">
      LOADING CERTIFICATES...
    </div>

    <div v-else-if="certificates.length === 0"
      class="p-12 text-center border-4 border-black bg-white shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] flex flex-col items-center gap-4">
      <div class="bg-gray-100 p-4 rounded-full border-2 border-black">
        <Icon icon="lucide:award" class="text-4xl text-gray-400" />
      </div>
      <div>
        <h3 class="font-bold text-xl uppercase">No Certificates Found</h3>
        <p class="font-mono text-gray-500 mb-6">You haven't uploaded any credentials yet.</p>
        <router-link to="/admin/dashboard/certificates/create"
          class="inline-block bg-black text-white hover:text-black hover:bg-gray-100 border-2 border-black px-6 py-3 font-bold uppercase shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:scale-105 transition-transform">
          Add Your First One!
        </router-link>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="cert in certificates" :key="cert.id"
        class="border-4 border-black bg-white p-4 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] hover:shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] hover:-translate-y-1 transition-all flex flex-col h-full relative">
        <button @click="handleToggleFeatured(cert)"
          class="absolute top-2 right-2 z-20 p-2 bg-white border-2 border-black rounded-full shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:scale-110 transition-transform"
          :title="cert.is_featured ? 'Unfeature Certificate' : 'Feature Certificate'">
          <Icon :icon="cert.is_featured ? 'lucide:star' : 'lucide:star-off'" class="w-5 h-5 transition-all duration-300"
            :class="cert.is_featured ? 'text-black fill-yellow-500 scale-110' : 'text-gray-300'" />
        </button>
        <div class="relative h-48 w-full border-2 border-black mb-4 bg-gray-100 overflow-hidden group">
          <img :src="cert.image_url || cert.image_path" loading="lazy" class="w-full h-full object-cover" alt="Certificate Image" />

          <a v-if="cert.credential_link" :href="cert.credential_link" target="_blank"
            class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
            <div
              class="bg-white text-black px-3 py-1 font-bold border-2 border-black text-xs uppercase flex items-center gap-1 shadow-[2px_2px_0px_0px_rgba(255,255,255,1)]">
              View Credential
              <Icon icon="lucide:external-link" />
            </div>
          </a>
        </div>

        <div class="flex-1">
          <div class="flex justify-between items-start mb-2">
            <span class="bg-black text-white text-[10px] px-2 py-0.5 font-mono uppercase font-bold">
              {{ cert.issuer || "Unknown Issuer" }}
            </span>
            <span class="text-xs font-mono text-gray-500">#{{ cert.id }}</span>
          </div>

          <h3 class="font-black text-lg uppercase leading-tight mb-2 line-clamp-2">
            {{ cert.title }}
          </h3>

          <div v-html="renderMarkdown(cert.description)"
            class="markdown-preview text-sm font-mono text-gray-600 line-clamp-3 mb-3"></div>

          <div class="flex flex-wrap gap-1.5 mb-2">
            <span v-if="cert.type"
              class="text-[10px] font-bold uppercase px-1.5 py-0.5 border border-black rounded-sm bg-[#E7E7E7]">
              {{ formatLabel(cert.type) }}
            </span>
            <span v-if="cert.start_date" class="text-[10px] font-mono text-gray-400 flex items-center gap-1">
              <Icon icon="lucide:calendar" class="w-3 h-3" />
              {{ formatDate(cert.start_date) }} → {{ formatDate(cert.end_date) }}
            </span>
          </div>
        </div>

        <div class="flex gap-3 mt-auto pt-4 border-t-2 border-black border-dashed">
          <router-link :to="`/admin/dashboard/certificates/edit/${cert.id}`"
            class="flex-1 bg-gray-200 hover:bg-gray-500 border-2 border-black py-2 font-bold text-center text-sm shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:bg-gray-200 active:translate-x-[2px] active:translate-y-[2px] active:shadow-none transition-all flex items-center justify-center gap-2">
            <Icon icon="lucide:pencil" class="w-4 h-4" />
            Edit
          </router-link>

          <button @click="handleDelete(cert.id)"
            class="bg-red-500 text-white hover:bg-red-600 flex-1 border-2 border-black py-2 font-bold text-center text-sm shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none transition-all flex items-center justify-center gap-2">
            <Icon icon="lucide:trash-2" class="w-4 h-4" />
            Delete
          </button>
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