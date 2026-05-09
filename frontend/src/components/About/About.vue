<script setup>
import { onMounted, ref, computed, nextTick, watch } from "vue";
import gsap from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
import { getProfile } from "../../lib/api/ProfileApi";
import NProgress from "nprogress";
import "nprogress/nprogress.css";

// --- STATE ---
const profile = ref({});
const isLoading = ref(true);

// --- COMPUTED ---
const aboutData = computed(() => profile.value.about || {});
gsap.registerPlugin(ScrollTrigger);

// --- FUNCTION ---
const fetchProfileData = async () => {
  isLoading.value = true;
  NProgress.start();
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
      class="-mt-30 md:-mt-12 min-h-screen flex justify-center py-24 px-4 sm:px-6 font-sans text-black selection:bg-black selection:text-white">
      <div class="container max-w-[650px] w-full flex flex-col space-y-12 mt-10 mx-auto">

        <!-- About Section -->
        <div class="anim-box flex flex-col space-y-4">
          <h1 class="anim-text text-2xl md:text-3xl font-bold tracking-wide text-black">
            Gilang Abdian
          </h1>

          <div class="anim-text space-y-6 text-sm md:text-base text-gray-500 font-normal leading-relaxed">
            <p>
              Hello, I am Gilang Abdian Anggara. As a dedicated Full-Stack Developer, I build web applications from
              the ground up—handling everything from complex database architectures to the user-facing interface.
            </p>
            <p>
              I focus on creating software that is functional, scalable, and easy to maintain. My goal is to blend
              solid engineering principles with a clean, relaxing aesthetic, ensuring that even the most complex
              systems feel simple and approachable.
            </p>


            <!-- What I Do Section -->
            <div class="anim-box space-y-4">
              <p class="anim-text text-sm md:text-base text-black font-normal">
                What I do:
              </p>

              <ul
                class="anim-text list-disc list-outside pl-5 space-y-2 text-sm md:text-base text-gray-500 font-normal leading-relaxed">
                <li>Designing responsive and accessible interfaces, translating ideas into clean, neat UIs for a
                  seamless user experience.</li>
                <li>Building secure server architectures, designing efficient database structures, and developing
                  RESTful APIs.</li>
                <li>Writing readable and modular code to ensure a well-organized project structure, preventing bugs and
                  simplifying updates.</li>
                <li>Handling the application deployment process to ensure stable and highly accessible production
                  environments.</li>
              </ul>
            </div>

            <p>
              Outside of programming, I enjoy making YouTube videos—you can find me on <a href="#"
                class="text-black hover:text-gray-600 underline transition-colors">this page</a>. I also like playing
              the guitar in my free time and singing my favorite songs to unwind.
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