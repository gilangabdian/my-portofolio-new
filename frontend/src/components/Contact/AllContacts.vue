<script setup>
import { onMounted, ref, nextTick, watch } from "vue";
import NProgress from "nprogress";
import "nprogress/nprogress.css";
import { alertError } from "../../lib/alert";
import { getAllContacts } from "../../lib/api/ContactApi";
import { Icon } from "@iconify/vue";
import gsap from "gsap";

const contacts = ref([]);
const isLoading = ref(true);

// --- Fetch Data ---
async function fetchContacts() {
  isLoading.value = true;
  NProgress.start();
  try {
    const response = await getAllContacts();
    const responseBody = await response.json();

    if (response.status === 200) {
      contacts.value = responseBody.data || responseBody;
    } else {
      await alertError(responseBody.message);
    }
  } catch (e) {
    console.error(`Error fetch contacts:`, e);
  } finally {
    NProgress.done();
    setTimeout(() => {
      isLoading.value = false;
      window.dispatchEvent(new CustomEvent("content-loaded"));
    }, 800);
  }
}

// --- GSAP Animation Trigger ---
watch(isLoading, (newVal) => {
  if (!newVal) {
    nextTick(() => {
      animateEntrance();
    });
  }
});

// --- GSAP Animation Logic ---
const animateEntrance = () => {
  const tl = gsap.timeline();

  tl.fromTo(
    ".comic-title",
    { y: 20, autoAlpha: 0 },
    {
      y: 0,
      autoAlpha: 1,
      duration: 0.6,
      ease: "back.out(1.5)",
    },
  );

  if (contacts.value.length > 0) {
    tl.fromTo(
      ".comic-panel",
      {
        y: 20,
        autoAlpha: 0,
      },
      {
        y: 0,
        autoAlpha: 1,
        duration: 0.5,
        ease: "power2.out",
        stagger: 0.05,
        clearProps: "all",
      },
      "-=0.3",
    );
  }
};

onMounted(async () => {
  await fetchContacts();
});
</script>

<template>
  <div
    class="-mt-16 mb-40 md:-mt-5 comic-container min-h-screen relative overflow-x-hidden text-black font-sans pb-60 md:pb-30">


    <div v-if="!isLoading" class="container mx-auto px-6 py-20 md:py-28 relative z-10 max-w-2xl">
      <div class="mb-12 text-center comic-title" style="opacity: 0; visibility: hidden">
        <h1 class="anim-text text-2xl md:text-3xl font-bold tracking-wide text-black">
          Contacts
        </h1>
        <p class="mt-4 font-sans text-gray-600 text-sm md:text-base max-w-xl mx-auto italic">
          "Let's connect."
        </p>
      </div>

      <div class="flex flex-wrap justify-center mt-8 gap-4 md:gap-6">
        <a v-for="contact in contacts" :key="contact.id" :href="contact.url" target="_blank"
          class="comic-panel group flex items-center gap-2 text-gray-600 hover:text-black transition-colors duration-300"
          style="opacity: 0; visibility: hidden">
          <Icon v-if="contact.icon" :icon="contact.icon" class="w-4 h-4 md:w-5 md:h-5" />
          <span class="text-sm md:text-base capitalize">
            {{ contact.platform_name }}
          </span>
          <Icon icon="mdi:arrow-top-right"
            class="w-3 h-3 md:w-4 md:h-4 opacity-0 -translate-x-2 transition-all duration-300 group-hover:opacity-100 group-hover:translate-x-0" />
        </a>
      </div>

      <div v-if="!isLoading && contacts.length === 0"
        class="text-center py-10 bg-gray-50 border border-black/20 border-dashed mt-8 rounded-lg shadow-sm">
        <Icon icon="mdi:signal-off" class="text-4xl mx-auto mb-2" />
        <p class="text-lg font-bold uppercase tracking-wide">NO SIGNAL DETECTED.</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.comic-container {
  /* removed background-color: #ffffff; to fix dark mode */
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.6s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>