<script setup>
import { onMounted, ref, computed, nextTick, watch } from "vue";
import gsap from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
import { getProfile } from "../../lib/api/ProfileApi";
import NProgress from "nprogress";
import "nprogress/nprogress.css";
import { RouterLink } from "vue-router";

// --- STATE ---
const profile = ref({});
const isLoading = ref(true);

// --- COMPUTED ---
const aboutData = computed(() => profile.value.about || {});
gsap.registerPlugin(ScrollTrigger);

// --- FUNCTION ---
const fetchProfileData = async () => {
  isLoading.value = true;
  // NProgress.start() is handled by router.beforeEach — no duplicate call needed
  try {
    const res = await getProfile();
    if (!res.ok) throw new Error("Failed to fetch");
    const json = await res.json();
    profile.value = json.data || json;
  } catch (error) {
    console.error("Error fetching profile:", error);
  } finally {
    NProgress.done();
    setTimeout(() => {
      isLoading.value = false;
      window.dispatchEvent(new CustomEvent("content-loaded"));
    }, 800);
  }
};

// --- LIFECYCLE ---
onMounted(async () => {
  await fetchProfileData();
});

// Animation trigger when loading finishes
watch(isLoading, (newVal) => {
  if (!newVal) {
    nextTick(() => {
      const tl = gsap.timeline({
        onComplete: () => {
          ScrollTrigger.refresh();
        },
      });

      tl.from(
        ".anim-text",
        {
          y: 20,
          opacity: 0,
          duration: 0.8,
          stagger: 0.1,
          ease: "power2.out",
        }
      )
        .from(
          ".anim-box",
          {
            y: 20,
            opacity: 0,
            duration: 0.8,
            stagger: 0.15,
            ease: "power2.out",
          },
          "-=0.6",
        );
      ScrollTrigger.refresh();
    });
  }
});
</script>

<template>
  <div class="min-h-screen mb-40">


    <section v-if="!isLoading"
      class="-mt-30 md:-mt-12 min-h-screen flex justify-center py-24 px-4 sm:px-6 font-sans text-black">
      <div class="container max-w-[650px] w-full flex flex-col space-y-12 mt-10 mx-auto">

        <!-- About Section -->
        <div class="anim-box flex flex-col space-y-4">
          <h1 class="anim-text text-2xl md:text-3xl font-bold tracking-wide text-black">
            Gilang Abdian
          </h1>

          <div class="anim-text space-y-6 text-sm md:text-base text-gray-700 font-normal leading-relaxed">
            <p>
              Hi, I'm Gilang Abdian Anggara. While my background covers the full stack of web development, my true
              passion and current focus are deeply rooted in Frontend Development. I love the challenge of turning
              complex logic into something beautiful, intuitive, and easy for people to use.
            </p>
            <p>
              I am dedicated to crafting digital experiences that are not just visually stunning, but also fast,
              accessible, and seamless. For me, great frontend work is about more than just aesthetics; it's about
              writing clean, maintainable code and building interfaces that feel natural on any device. My goal is to
              transform complex ideas into smooth, high-performance web applications that stay relevant as technology
              evolves.
            </p>


            <!-- What I Do Section -->
            <!-- <div class="anim-box space-y-4">
              <p class="anim-text text-sm md:text-base text-black font-normal">
                What I do:
              </p>

              <ul
                class="anim-text list-disc list-outside pl-5 space-y-2 text-sm md:text-base text-gray-700 font-normal leading-relaxed">
                <li><strong>Crafting Modern UIs</strong>: Building responsive and beautiful interfaces using the latest web technologies.</li>
                <li><strong>Performance & Accessibility</strong>: Ensuring websites are fast, lightweight, and accessible to everyone.</li>
                <li><strong>Clean & Scalable Code</strong>: Writing modular and well-organized code that is easy to maintain and grow.</li>
                <li><strong>Seamless Interaction</strong>: Creating smooth animations and micro-interactions to enhance the user experience.</li>
              </ul>
            </div> -->

            <p>
              Outside of programming, I enjoy making <a href="https://www.youtube.com/@jeezfay" target="_blank"
                class="underline text-black decoration-black/20 hover:decoration-black dark:decoration-white/20 dark:hover:decoration-white underline-offset-4 transition-all duration-300">YouTube
                videos</a>, playing the guitar, and drawing. I post my <RouterLink to="/artworks" class="underline text-black decoration-black/20 hover:decoration-black dark:decoration-white/20 dark:hover:decoration-white underline-offset-4 transition-all duration-300">drawings on this page</RouterLink>. Also, I am constantly finding new ways to blend technology with creativity to stay inspired.
            </p>
          </div>
        </div>


      </div>
    </section>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.6s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>