<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { getAllProjects } from "../../../lib/api/ProjectApi";
import { getAllArtworks } from "../../../lib/api/ArtworkApi";
import { getAllCertificates } from "../../../lib/api/CertificateApi";
import { getSkills } from "../../../lib/api/SkillApi";
import { getAllContacts } from "../../../lib/api/ContactApi";
import { getAllPhotos } from "../../../lib/api/PhotoApi";
import { getAllBlogsAdmin } from "../../../lib/api/BlogApi";
import { getVisitorCount, adminGetVisitors, adminDeleteVisitor, adminClearAllVisitors } from "../../../lib/api/VisitorApi";
import { alertConfirmVisitor, alertConfirmClearAllVisitors, alertSuccessVisitor } from "../../../lib/alert";
import { Icon } from "@iconify/vue";

const stats = ref({
  projects: 0,
  artworks: 0,
  certificates: 0,
  skills: 0,
  contacts: 0,
  visitors: 0,
  photos: 0,
  blogs: 0,
});

const visitorsList = ref([]);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
});

const isLoading = ref(true);
const isDbConnected = ref(false);
const currentTime = ref("");
let timeInterval = null;
let statusInterval = null;

const getDeviceIcon = (deviceType) => {
  if (deviceType === 'mobile') return 'lucide:smartphone';
  if (deviceType === 'tablet') return 'lucide:tablet';
  if (deviceType === 'robot') return 'lucide:bot';
  return 'lucide:monitor';
};

// --- Logic Jam Digital ---
const updateTime = () => {
  currentTime.value = new Date().toLocaleTimeString("en-US", {
    hour12: false,
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });
};

// --- Logic Fetch Data (Initial Load) ---
async function fetchData(apiCall, key) {
  try {
    const response = await apiCall();
    if (response.status === 200) {
      isDbConnected.value = true;
      const responseBody = await response.json();
      const data = responseBody.data || responseBody;
      if (key === 'visitors') {
        stats.value[key] = data.total_visitors || 0;
      } else {
        stats.value[key] = Array.isArray(data) ? data.length : 0;
      }
    } else {
      isDbConnected.value = false;
    }
  } catch (e) {
    isDbConnected.value = false;
    console.error(`Error fetching ${key}:`, e);
  }
}

async function fetchVisitorsList(page = 1) {
  try {
    const response = await adminGetVisitors(page);
    if (response.status === 200) {
      const responseBody = await response.json();
      visitorsList.value = responseBody.data || [];
      pagination.value = {
        current_page: responseBody.current_page,
        last_page: responseBody.last_page,
        total: responseBody.total,
      };
    }
  } catch (e) {
    console.error("Error fetching visitors list:", e);
  }
}

async function deleteVisitor(id) {
  const confirmed = await alertConfirmVisitor("You won't be able to revert this!");

  if (confirmed) {
    try {
      const response = await adminDeleteVisitor(id);
      if (response.status === 200) {
        await alertSuccessVisitor("Deleted!", "Visitor record has been deleted.");
        fetchVisitorsList(pagination.value.current_page);
        fetchData(getVisitorCount, "visitors");
      }
    } catch (e) {
      console.error("Error deleting visitor:", e);
    }
  }
}

async function clearAllVisitors() {
  const confirmed = await alertConfirmClearAllVisitors("This will permanently delete all visitor records!");

  if (confirmed) {
    try {
      const response = await adminClearAllVisitors();
      if (response.status === 200) {
        await alertSuccessVisitor("Cleared!", "All visitor records have been deleted.");
        fetchVisitorsList(1);
        fetchData(getVisitorCount, "visitors");
      }
    } catch (e) {
      console.error("Error clearing visitors:", e);
    }
  }
}

// --- Logic Cek Koneksi (Background Process) ---
async function checkConnection() {
  try {
    const response = await getAllProjects();
    if (response.status === 200) {
      if (!isDbConnected.value) isDbConnected.value = true;
    } else {
      isDbConnected.value = false;
    }
  } catch (e) {
    isDbConnected.value = false;
  }
}

onMounted(async () => {
  updateTime();
  timeInterval = setInterval(updateTime, 1000);

  isLoading.value = true;
  await Promise.all([
    fetchData(getAllProjects, "projects"),
    fetchData(getAllArtworks, "artworks"),
    fetchData(getAllCertificates, "certificates"),
    fetchData(getSkills, "skills"),
    fetchData(getAllContacts, "contacts"),
    fetchData(getAllPhotos, "photos"),
    fetchData(getAllBlogsAdmin, "blogs"),
    fetchData(getVisitorCount, "visitors"),
    fetchVisitorsList(),
  ]);
  isLoading.value = false;

  statusInterval = setInterval(checkConnection, 5000);
});

onUnmounted(() => {
  if (timeInterval) clearInterval(timeInterval);
  if (statusInterval) clearInterval(statusInterval);
});
</script>

<template>
  <div class="space-y-8 font-sans text-black">
    <div
      class="bg-white border-4 border-black p-6 shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] flex justify-between items-center">
      <div>
        <h1 class="text-3xl md:text-4xl font-black uppercase tracking-tighter">
          Dashboard
          <span class="bg-black text-white px-2 py-1 inline-block -skew-x-6">Overview</span>
        </h1>
        <p class="font-bold font-mono text-gray-700 mt-2">Welcome back, Admin! Here is your portfolio report.</p>
      </div>
      <div
        class="hidden md:flex bg-white border-2 border-black p-4 rounded-full shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:translate-y-[-2px] hover:shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] transition-all">
        <Icon icon="lucide:layout-dashboard" class="text-4xl" />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- Total Projects -->
      <div
        class="bg-white border-4 border-black p-5 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] hover:translate-y-[-4px] hover:shadow-[10px_10px_0px_0px_rgba(0,0,0,1)] hover:bg-gray-100 transition-all cursor-default relative overflow-hidden group">
        <div class="absolute -right-6 -top-6 opacity-10 group-hover:opacity-20 transition-opacity">
          <Icon icon="lucide:folder-kanban" class="text-9xl" />
        </div>
        <div class="flex justify-between items-start relative z-10">
          <div>
            <p class="font-black text-sm uppercase mb-1 tracking-wide bg-black text-white inline-block px-1">Total Projects</p>
            <h3 class="text-5xl font-black mt-2">{{ isLoading ? "..." : stats.projects }}</h3>
          </div>
          <div class="bg-black text-white p-2 border-2 border-black rotate-3 group-hover:-rotate-3 transition-transform">
             <Icon icon="lucide:folder-kanban" class="text-3xl" />
          </div>
        </div>
        <div
          class="mt-4 pt-4 border-t-4 border-black text-xs font-bold font-mono flex items-center gap-2 relative z-10">
          <Icon icon="lucide:history" />
          Updated recently
        </div>
      </div>

      <!-- Artworks -->
      <div
        class="bg-white border-4 border-black p-5 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] hover:translate-y-[-4px] hover:shadow-[10px_10px_0px_0px_rgba(0,0,0,1)] hover:bg-gray-100 transition-all cursor-default relative overflow-hidden group">
        <div class="absolute -right-6 -top-6 opacity-10 group-hover:opacity-20 transition-opacity">
          <Icon icon="lucide:palette" class="text-9xl" />
        </div>
        <div class="flex justify-between items-start relative z-10">
          <div>
            <p class="font-black text-sm uppercase mb-1 tracking-wide bg-black text-white inline-block px-1">Artworks</p>
            <h3 class="text-5xl font-black mt-2">{{ isLoading ? "..." : stats.artworks }}</h3>
          </div>
          <div class="bg-black text-white p-2 border-2 border-black rotate-2 group-hover:-rotate-3 transition-transform">
             <Icon icon="lucide:palette" class="text-3xl" />
          </div>
        </div>
        <div
          class="mt-4 pt-4 border-t-4 border-black text-xs font-bold font-mono flex items-center gap-2 relative z-10">
          <Icon icon="lucide:image" />
          Gallery items
        </div>
      </div>

      <!-- Certificates -->
      <div
        class="bg-white border-4 border-black p-5 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] hover:translate-y-[-4px] hover:shadow-[10px_10px_0px_0px_rgba(0,0,0,1)] hover:bg-gray-100 transition-all cursor-default relative overflow-hidden group">
        <div class="absolute -right-6 -top-6 opacity-10 group-hover:opacity-20 transition-opacity">
          <Icon icon="lucide:award" class="text-9xl" />
        </div>
        <div class="flex justify-between items-start relative z-10">
          <div>
            <p class="font-black text-sm uppercase mb-1 tracking-wide bg-black text-white inline-block px-1">Certificates</p>
            <h3 class="text-5xl font-black mt-2">{{ isLoading ? "..." : stats.certificates }}</h3>
          </div>
          <div class="bg-black text-white p-2 border-2 border-black rotate-6 group-hover:-rotate-3 transition-transform">
             <Icon icon="lucide:award" class="text-3xl" />
          </div>
        </div>
        <div
          class="mt-4 pt-4 border-t-4 border-black text-xs font-bold font-mono flex items-center gap-2 relative z-10">
          <Icon icon="lucide:check-circle" />
          Valid credentials
        </div>
      </div>

      <!-- Photos -->
      <div
        class="bg-white border-4 border-black p-5 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] hover:translate-y-[-4px] hover:shadow-[10px_10px_0px_0px_rgba(0,0,0,1)] hover:bg-gray-100 transition-all cursor-default relative overflow-hidden group">
        <div class="absolute -right-6 -top-6 opacity-10 group-hover:opacity-20 transition-opacity">
          <Icon icon="ri:camera-3-line" class="text-9xl" />
        </div>
        <div class="flex justify-between items-start relative z-10">
          <div>
            <p class="font-black text-sm uppercase mb-1 tracking-wide bg-black text-white inline-block px-1">Photos</p>
            <h3 class="text-5xl font-black mt-2">{{ isLoading ? "..." : stats.photos }}</h3>
          </div>
          <div class="bg-black text-white p-2 border-2 border-black rotate-2 group-hover:rotate-6 transition-transform">
             <Icon icon="ri:camera-3-line" class="text-3xl" />
          </div>
        </div>
        <div
          class="mt-4 pt-4 border-t-4 border-black text-xs font-bold font-mono flex items-center gap-2 relative z-10">
          <Icon icon="lucide:image" />
          Photographic memories
        </div>
      </div>

      <!-- Blogs -->
      <div
        class="bg-white border-4 border-black p-5 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] hover:translate-y-[-4px] hover:shadow-[10px_10px_0px_0px_rgba(0,0,0,1)] hover:bg-gray-100 transition-all cursor-default relative overflow-hidden group">
        <div class="absolute -right-6 -top-6 opacity-10 group-hover:opacity-20 transition-opacity">
          <Icon icon="material-symbols-light:post-outline" class="text-9xl" />
        </div>
        <div class="flex justify-between items-start relative z-10">
          <div>
            <p class="font-black text-sm uppercase mb-1 tracking-wide bg-black text-white inline-block px-1">Blogs</p>
            <h3 class="text-5xl font-black mt-2">{{ isLoading ? "..." : stats.blogs }}</h3>
          </div>
          <div class="bg-black text-white p-2 border-2 border-black -rotate-3 group-hover:-rotate-6 transition-transform">
             <Icon icon="material-symbols-light:post-outline" class="text-3xl" />
          </div>
        </div>
        <div
          class="mt-4 pt-4 border-t-4 border-black text-xs font-bold font-mono flex items-center gap-2 relative z-10">
          <Icon icon="lucide:file-text" />
          Articles & Stories
        </div>
      </div>

      <!-- Tech Stack -->
      <div
        class="bg-white border-4 border-black p-5 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] hover:translate-y-[-4px] hover:shadow-[10px_10px_0px_0px_rgba(0,0,0,1)] hover:bg-gray-100 transition-all cursor-default relative overflow-hidden group">
        <div class="absolute -right-6 -top-6 opacity-10 group-hover:opacity-20 transition-opacity">
          <Icon icon="lucide:zap" class="text-9xl" />
        </div>
        <div class="flex justify-between items-start relative z-10">
          <div>
            <p class="font-black text-sm uppercase mb-1 tracking-wide bg-black text-white inline-block px-1">Tech Stack</p>
            <h3 class="text-5xl font-black mt-2">{{ isLoading ? "..." : stats.skills }}</h3>
          </div>
          <div class="bg-black text-white p-2 border-2 border-black -rotate-6 group-hover:rotate-3 transition-transform">
             <Icon icon="lucide:zap" class="text-3xl" />
          </div>
        </div>
        <div
          class="mt-4 pt-4 border-t-4 border-black text-xs font-bold font-mono flex items-center gap-2 relative z-10">
          <Icon icon="lucide:code" />
          Active skills
        </div>
      </div>

      <!-- Social Links -->
      <div
        class="bg-white border-4 border-black p-5 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] hover:translate-y-[-4px] hover:shadow-[10px_10px_0px_0px_rgba(0,0,0,1)] hover:bg-gray-100 transition-all cursor-default relative overflow-hidden group">
        <div class="absolute -right-6 -top-6 opacity-10 group-hover:opacity-20 transition-opacity">
          <Icon icon="lucide:share-2" class="text-9xl" />
        </div>
        <div class="flex justify-between items-start relative z-10">
          <div>
            <p class="font-black text-sm uppercase mb-1 tracking-wide bg-black text-white inline-block px-1">Social Links</p>
            <h3 class="text-5xl font-black mt-2">{{ isLoading ? "..." : stats.contacts }}</h3>
          </div>
          <div class="bg-black text-white p-2 border-2 border-black rotate-3 group-hover:-rotate-3 transition-transform">
             <Icon icon="lucide:share-2" class="text-3xl" />
          </div>
        </div>
        <div
          class="mt-4 pt-4 border-t-4 border-black text-xs font-bold font-mono flex items-center gap-2 relative z-10">
          <Icon icon="lucide:globe" />
          Active platforms
        </div>
      </div>

      <!-- Total Visitors -->
      <div
        class="bg-white border-4 border-black p-5 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] hover:translate-y-[-4px] hover:shadow-[10px_10px_0px_0px_rgba(0,0,0,1)] hover:bg-gray-100 transition-all cursor-default relative overflow-hidden group">
        <div class="absolute -right-6 -top-6 opacity-10 group-hover:opacity-20 transition-opacity">
          <Icon icon="lucide:users" class="text-9xl" />
        </div>
        <div class="flex justify-between items-start relative z-10">
          <div>
            <p class="font-black text-sm uppercase mb-1 tracking-wide bg-black text-white inline-block px-1">Total Visitors</p>
            <h3 class="text-5xl font-black mt-2">{{ isLoading ? "..." : stats.visitors }}</h3>
          </div>
          <div class="bg-black text-white p-2 border-2 border-black -rotate-3 group-hover:rotate-6 transition-transform">
             <Icon icon="lucide:users" class="text-3xl" />
          </div>
        </div>
        <div
          class="mt-4 pt-4 border-t-4 border-black text-xs font-bold font-mono flex items-center gap-2 relative z-10">
          <Icon icon="lucide:activity" />
          Unique Homepage Visits
        </div>
      </div>
    </div>

    <div class="w-full">
      <div class="bg-white border-4 border-black p-6 shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] hover:-translate-y-1 hover:shadow-[12px_12px_0px_0px_rgba(0,0,0,1)] transition-all">
        <h3 class="font-black text-xl mb-4 border-b-4 border-black pb-2 flex items-center gap-2 uppercase">
          <span class="bg-black text-white px-2 py-1 inline-block -skew-x-6">SYSTEM STATUS</span>
        </h3>
        <ul class="space-y-4 font-mono text-sm font-bold mt-6">
          <li class="flex justify-between items-center p-3 border-2 border-black shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] group hover:-translate-y-1 transition-transform bg-white">
            <span class="uppercase tracking-widest text-xs">Backend Connection</span>
            <span v-if="isDbConnected" class="text-black flex items-center gap-2 transition-all duration-500">
              <span class="w-3 h-3 bg-black rounded-full animate-pulse"></span>
              ONLINE (Laravel)
            </span>
            <span v-else class="text-black flex items-center gap-2 animate-pulse transition-all duration-500">
              <span class="w-3 h-3 bg-gray-400 rounded-full"></span>
              OFFLINE
            </span>
          </li>

          <li class="flex justify-between items-center p-3 border-2 border-black shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] group hover:-translate-y-1 transition-transform bg-white">
            <span class="uppercase tracking-widest text-xs">Database Status</span>
            <span v-if="isDbConnected" class="text-black flex items-center gap-2 transition-all duration-500">
              <Icon icon="lucide:database" class="text-lg" />
              CONNECTED
            </span>
            <span v-else class="text-black flex items-center gap-2 animate-pulse transition-all duration-500">
              <Icon icon="lucide:database-zap" class="text-lg" />
              DISCONNECTED
            </span>
          </li>

          <li class="flex justify-between items-center p-3 border-2 border-black shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] group hover:-translate-y-1 transition-transform bg-black text-white">
            <span class="uppercase tracking-widest text-xs">Server Time</span>
            <span class="font-bold font-mono tracking-widest">{{ currentTime }}</span>
          </li>
        </ul>
      </div>
    </div>

    <!-- Visitors List Section -->
    <div class="w-full mt-8">
      <div class="bg-white border-4 border-black p-6 shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] hover:-translate-y-1 hover:shadow-[12px_12px_0px_0px_rgba(0,0,0,1)] transition-all">
        <h3 class="font-black text-xl mb-4 border-b-4 border-black pb-2 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 uppercase">
          <div class="flex items-center gap-2">
            <span class="bg-black text-white px-2 py-1 inline-block -skew-x-6">VISITOR HISTORY</span>
            <span class="text-sm font-mono lowercase opacity-60">({{ pagination.total }} records)</span>
          </div>
          <button @click="clearAllVisitors" 
            class="bg-red-500 text-white border-2 border-black px-3 py-1 text-xs font-bold shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:translate-y-[-1px] hover:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] active:translate-y-[1px] active:shadow-[0px_0px_0px_0px_rgba(0,0,0,1)] transition-all flex items-center gap-2">
            <Icon icon="lucide:trash-2" />
            CLEAR ALL LOGS
          </button>
        </h3>
        
        <div v-if="visitorsList.length === 0" class="text-center py-8 border-2 border-dashed border-gray-400 mt-6">
          <Icon icon="lucide:ghost" class="text-4xl mx-auto mb-2 text-gray-400" />
          <p class="font-mono font-bold text-gray-500">No visitors logged yet.</p>
        </div>
        
        <div v-else class="overflow-x-auto mt-6">
          <table class="w-full text-left font-mono text-sm border-2 border-black">
            <thead class="bg-black text-white">
              <tr>
                <th class="p-3 border-r-2 border-black max-w-[50px]">LATEST</th>
                <th class="p-3 border-r-2 border-black">DEVICE ID</th>
                <th class="p-3 border-r-2 border-black text-center">DEVICE</th>
                <th class="p-3 border-r-2 border-black">OS</th>
                <th class="p-3 border-r-2 border-black">BROWSER</th>
                <th class="p-3 text-center">ACTION</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(visitor, index) in visitorsList" :key="visitor.id" 
                  class="border-t-2 border-black hover:bg-yellow-100 transition-colors"
                  :class="{'bg-gray-50': index % 2 === 0}">
                <td class="p-3 border-r-2 border-black font-bold">{{ new Date(visitor.updated_at).toLocaleDateString() }}</td>
                <td class="p-3 border-r-2 border-black truncate max-w-[150px]" :title="visitor.device_id">
                  {{ visitor.device_id.split('-')[0] }}...
                </td>
                <td class="p-3 border-r-2 border-black flex justify-center items-center gap-2 font-bold uppercase">
                  <Icon :icon="getDeviceIcon(visitor.device_type)" class="text-xl" />
                  <span class="hidden md:inline">{{ visitor.device_type || 'Unknown' }}</span>
                </td>
                <td class="p-3 border-r-2 border-black">{{ visitor.os || '-' }}</td>
                <td class="p-3 border-r-2 border-black font-bold text-xs truncate max-w-[100px]" :title="visitor.browser">
                  {{ visitor.browser || '-' }}
                </td>
                <td class="p-2 text-center">
                  <button @click="deleteVisitor(visitor.id)" 
                    class="bg-white text-red-500 border-2 border-black p-1 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:bg-red-50 active:translate-y-[1px] active:shadow-none transition-all">
                    <Icon icon="lucide:trash-2" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <div class="mt-6 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-xs font-mono font-bold opacity-60 italic">
              * Page {{ pagination.current_page }} of {{ pagination.last_page }}
            </p>
            
            <div class="flex gap-2">
              <button 
                @click="fetchVisitorsList(pagination.current_page - 1)"
                :disabled="pagination.current_page === 1"
                class="bg-white border-2 border-black px-4 py-2 font-black text-sm shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:translate-y-[-2px] hover:shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] disabled:opacity-30 disabled:translate-y-0 disabled:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] transition-all flex items-center gap-2">
                <Icon icon="lucide:chevron-left" />
                PREV
              </button>
              
              <button 
                @click="fetchVisitorsList(pagination.current_page + 1)"
                :disabled="pagination.current_page === pagination.last_page"
                class="bg-black text-white border-2 border-black px-4 py-2 font-black text-sm shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:translate-y-[-2px] hover:shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] disabled:opacity-30 disabled:translate-y-0 disabled:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] transition-all flex items-center gap-2 font-mono">
                NEXT
                <Icon icon="lucide:chevron-right" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
