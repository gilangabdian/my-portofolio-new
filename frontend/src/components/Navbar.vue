<script setup>
import { Icon } from "@iconify/vue";
import { ref, onMounted, onUnmounted, watch, nextTick } from "vue";
import gsap from "gsap";
import { useRoute } from "vue-router";
import { useDark, useToggle } from "@vueuse/core";

const isDark = useDark();
if (localStorage.getItem('vueuse-color-scheme') === null) {
  isDark.value = true; // Default to dark theme
}
const toggleDark = useToggle(isDark);

const route = useRoute();
const menus = [
  { name: "Home", href: "/", icon: "mdi:home-variant-outline" },
  { name: "About", href: "/about", icon: "mdi:card-account-details-outline" },
  { name: "Projects", href: "/projects", icon: "mdi:folder-outline" },
  { name: "Certificates", href: "/certificates", icon: "mdi:certificate-outline" },
  { name: "Services", href: "/services", icon: "mdi:code-tags" },
  { name: "Contacts", href: "/contacts", icon: "mdi:email-outline" },
];

const menuContainer = ref(null);
const navRef = ref(null);

// --- Scroll Hint Animation ---
const playScrollHint = () => {
  const el = menuContainer.value;
  if (el && window.innerWidth < 768) {
    setTimeout(() => {
      el.scrollLeft = 0;
      gsap.fromTo(
        el,
        { scrollLeft: 0 },
        {
          scrollLeft: 60,
          duration: 1.2,
          delay: 0.2,
          ease: "power2.inOut",
          yoyo: true,
          repeat: 1,
          overwrite: "auto",
        },
      );
    }, 600);
  }
};

// --- Footer Avoidance Logic ---
let rafId = null;

const checkFooterOverlap = () => {
  const footerEl = document.querySelector("footer");
  if (!footerEl || !navRef.value) return;

  const footerRect = footerEl.getBoundingClientRect();
  const navEl = navRef.value;
  const navHeight = navEl.offsetHeight;
  const viewportHeight = window.innerHeight;
  const isMobile = window.innerWidth < 768;

  // We explicitly include xPercent: -50 and left: 50% in every call
  // to prevent CSS/GSAP conflicts.
  if (isMobile) {
    // Keep it floating above the footer on mobile (App-like navigation)
    gsap.to(navEl, {
      y: 0,
      xPercent: -50,
      left: "50%",
      duration: 0.3,
      overwrite: "auto",
      ease: "power2.out"
    });
  } else {
    const navBottom = 16 + navHeight;
    if (footerRect.top < navBottom) {
      const pushAmount = navBottom - footerRect.top;
      gsap.set(navEl, {
        y: -pushAmount,
        xPercent: -50,
        left: "50%",
        force3D: true
      });
    } else {
      gsap.to(navEl, {
        y: 0,
        xPercent: -50,
        left: "50%",
        duration: 0.3,
        overwrite: "auto",
        ease: "power2.out"
      });
    }
  }
};

const onScroll = () => {
  if (rafId) cancelAnimationFrame(rafId);
  rafId = requestAnimationFrame(checkFooterOverlap);
};

const resetNavbarPosition = () => {
  if (navRef.value) {
    gsap.killTweensOf(navRef.value);
    // Use clearProps sparingly, better to set explicit values
    gsap.set(navRef.value, {
      y: 0,
      xPercent: -50,
      left: "50%",
      x: 0,
      clearProps: "transform" // Optional: clean slate
    });
    // Re-set immediately to ensure GSAP internal state is correct
    gsap.set(navRef.value, {
      y: 0,
      xPercent: -50,
      left: "50%",
      x: 0
    });
  }
};

// --- Lifecycle ---
onMounted(() => {
  // Take control immediately
  resetNavbarPosition();

  if (route.path === "/") {
    window.addEventListener("app-loading-done", playScrollHint, { once: true });
  } else {
    playScrollHint();
  }

  window.addEventListener("scroll", onScroll, { passive: true });
  window.addEventListener("content-loaded", onScroll);
  window.addEventListener("resize", onScroll, { passive: true });

  // Re-check after a brief delay for any dynamic layout shifts
  setTimeout(checkFooterOverlap, 100);
  setTimeout(checkFooterOverlap, 600);
});

watch(
  () => route.path,
  () => {
    window.scrollTo(0, 0);
    resetNavbarPosition();
    setTimeout(checkFooterOverlap, 600);
  },
);

onUnmounted(() => {
  window.removeEventListener("app-loading-done", playScrollHint);
  window.removeEventListener("scroll", onScroll);
  window.removeEventListener("content-loaded", onScroll);
  window.removeEventListener("resize", onScroll);
  if (rafId) cancelAnimationFrame(rafId);
});
</script>

<template>
  <nav ref="navRef" class="fixed bottom-4 md:top-4 md:bottom-auto left-1/2 z-51 w-[95%] md:max-w-fit">
    <div
      class="bg-white/90 dark:bg-[#1e1e1e]/90 backdrop-blur-md border border-black/20 dark:border-white/10 rounded-2xl md:rounded-full px-2 py-2 md:px-6 md:py-2 shadow-[0_4px_20px_rgba(0,0,0,0.05)] flex items-center transition-all duration-300 hover:shadow-[0_8px_30px_rgba(0,0,0,0.08)] dark:hover:shadow-[0_8px_30px_rgba(255,255,255,0.05)] overflow-hidden">
      <div
        class="hidden md:block font-serif font-bold text-xl tracking-tighter mr-4 border-r border-black/20 dark:border-white/20 pr-4 text-black dark:text-white">
        A
      </div>

      <div ref="menuContainer"
        class="flex gap-2 items-center w-full md:w-auto overflow-x-auto no-scrollbar md:overflow-visible px-1 scroll-fade min-w-0">
        <RouterLink v-for="item in menus" :key="item.name" :to="item.href"
          class="group flex-shrink-0 flex flex-col md:flex-row items-center justify-center gap-1 md:gap-1.5 p-2 md:px-3 md:py-1.5 rounded-xl md:rounded-full text-[10px] md:text-sm font-bold transition-all duration-200 border border-transparent hover:border-black/20 dark:hover:border-white/20 active:scale-95 whitespace-nowrap text-gray-700 dark:text-gray-300 dark:hover:text-white"
          exact-active-class="active-nav-item bg-black text-white dark:!bg-[#ffffff] shadow-md md:shadow-none !border-transparent">
          <Icon :icon="item.icon" class="w-5 h-5 md:w-4 md:h-4 transition-transform group-hover:scale-110" />
          <span class="uppercase tracking-tighter md:tracking-normal md:capitalize">
            {{ item.name }}
          </span>
        </RouterLink>
      </div>

      <!-- Theme Toggle -->
      <div class="border-l border-black/20 dark:border-white/20 pl-2 ml-1 md:pl-4 md:ml-2 flex-shrink-0">
        <button @click="toggleDark()" :title="isDark ? 'Switch to light theme' : 'Switch to dark theme'"
          class="cursor-pointer flex items-center justify-center p-2 rounded-full transition-all duration-300 hover:bg-black/5 dark:hover:bg-white/10 text-gray-700 dark:text-gray-300 dark:hover:text-white active:scale-95">
          <Icon :icon="isDark ? 'lucide:sun' : 'si:moon-line'" class="w-5 h-5 md:w-4 md:h-4" />
        </button>
      </div>
    </div>
  </nav>
</template>

<style scoped>
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.no-scrollbar::-webkit-scrollbar {
  display: none;
}

html:not(.dark) .router-link-exact-active svg {
  color: white !important;
  opacity: 1 !important;
}



@media (max-width: 768px) {
  .scroll-fade {
    mask-image: linear-gradient(to right, black 80%, transparent 100%);
    -webkit-mask-image: linear-gradient(to right, black 80%, transparent 100%);
  }
}
</style>