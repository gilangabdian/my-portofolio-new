<script setup>
import { ref, onMounted, onUnmounted, computed } from "vue";
import { Icon } from "@iconify/vue";

// Props dari DashboardAdmin
const props = defineProps({
  isMobileOpen: Boolean,
});

const emit = defineEmits(["close-mobile"]);

// State
const sidebarWidth = ref(260);
const isResizing = ref(false);
const isCollapsed = ref(false);
const minWidth = 80;
const maxWidth = 400;

// State Reactive untuk Lebar Layar
const windowWidth = ref(typeof window !== "undefined" ? window.innerWidth : 1000);

const menuItems = [
  { name: "Dashboard", icon: "lucide:layout-dashboard", route: "/admin/dashboard" },
  { name: "Profile", icon: "lucide:user", route: "/admin/dashboard/profile" },
  { name: "Experiences", icon: "lucide:briefcase", route: "/admin/dashboard/experiences" },
  { name: "Projects", icon: "lucide:folder-kanban", route: "/admin/dashboard/projects" },
  { name: "Certificates", icon: "lucide:award", route: "/admin/dashboard/certificates" },
  { name: "Skills", icon: "lucide:zap", route: "/admin/dashboard/skills" },
  { name: "Artworks", icon: "lucide:palette", route: "/admin/dashboard/artworks" },
  { name: "Photos", icon: "ri:camera-3-line", route: "/admin/dashboard/photos" },
  { name: "Blogs", icon: "material-symbols-light:post-outline", route: "/admin/dashboard/blogs" },
  { name: "Contacts", icon: "lucide:share-2", route: "/admin/dashboard/contacts" },
];

const showContent = computed(() => {
  return windowWidth.value < 768 || !isCollapsed.value;
});

const onResize = () => {
  windowWidth.value = window.innerWidth;
};

onMounted(() => {
  window.addEventListener("resize", onResize);
});

onUnmounted(() => {
  window.removeEventListener("resize", onResize);
});

// Logic Resizing
const startResize = () => {
  if (windowWidth.value < 768) return;
  isResizing.value = true;
  document.addEventListener("mousemove", handleResize);
  document.addEventListener("mouseup", stopResize);
  document.body.style.cursor = "col-resize";
  document.body.style.userSelect = "none";
};

const handleResize = (e) => {
  if (isResizing.value) {
    let newWidth = e.clientX;
    if (newWidth < minWidth) newWidth = minWidth;
    if (newWidth > maxWidth) newWidth = maxWidth;
    sidebarWidth.value = newWidth;
    isCollapsed.value = newWidth < 100;
  }
};

const stopResize = () => {
  isResizing.value = false;
  document.removeEventListener("mousemove", handleResize);
  document.removeEventListener("mouseup", stopResize);
  document.body.style.cursor = "default";
  document.body.style.userSelect = "";
};

const handleSidebarToggle = () => {
  if (windowWidth.value < 768) {
    emit("close-mobile");
  } else {
    isCollapsed.value = !isCollapsed.value;
    sidebarWidth.value = isCollapsed.value ? minWidth : 260;
  }
};

const computedSidebarStyle = computed(() => {
  if (isResizing.value || windowWidth.value >= 768) {
    return { width: `${sidebarWidth.value}px` };
  }
  return { width: "280px" };
});
</script>

<template>
  <aside
    class="fixed md:sticky top-0 left-0 z-50 bg-white border-r-4 border-black flex flex-col h-[100dvh]"
    :class="[
      props.isMobileOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
      isResizing ? 'transition-none' : 'transition-[width,transform] duration-300 ease-in-out',
    ]"
    :style="computedSidebarStyle">
    <div
      class="h-16 flex items-center justify-between md:justify-center px-4 md:px-0 border-b-4 border-black bg-white overflow-hidden whitespace-nowrap relative">
      <h1 v-if="showContent" class="font-black text-xl tracking-tighter">
        ADMIN
        <span class="bg-black text-white px-1">PANEL</span>
      </h1>
      <span v-else class="font-black text-xl bg-black text-white px-2">A</span>

      <button
        @click="$emit('close-mobile')"
        class="md:hidden p-1 border-2 border-black bg-black text-white hover:bg-gray-800 flex items-center justify-center">
        <Icon icon="lucide:x" width="20" height="20" />
      </button>
    </div>

    <nav class="flex-1 overflow-y-auto p-2 space-y-2">
      <router-link
        v-for="item in menuItems"
        :key="item.name"
        :to="item.route"
        @click="$emit('close-mobile')"
        class="flex items-center p-3 border-2 border-transparent hover:border-black hover:bg-gray-100 hover:text-black transition-all cursor-pointer"
        exact-active-class="bg-black text-white border-black shadow-[4px_4px_0px_0px_rgba(200,200,200,1)]"
        :class="{ 'justify-center': !showContent }">
        <Icon :icon="item.icon" class="text-2xl shrink-0" />

        <span v-if="showContent" class="ml-3 font-bold font-mono truncate">{{ item.name }}</span>
      </router-link>
    </nav>

    <button
      @click="handleSidebarToggle"
      class="flex p-2 border-t-4 border-black hover:bg-gray-100 hover:text-black justify-center items-center cursor-pointer w-full text-black">
      <Icon :icon="!showContent ? 'lucide:chevron-right' : 'lucide:chevron-left'" width="24" height="24" />
    </button>

    <div
      @mousedown="startResize"
      class="hidden md:block absolute top-0 -right-2 w-5 h-full cursor-col-resize hover:bg-black opacity-0 hover:opacity-[0.05] transition-opacity z-50"
      title="Drag to resize"></div>
  </aside>
</template>
