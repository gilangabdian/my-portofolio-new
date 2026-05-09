<script setup>
import { computed, onMounted, nextTick, ref, onUnmounted } from "vue";
import { Icon } from "@iconify/vue";
import gsap from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
import { marked } from "marked";

gsap.registerPlugin(ScrollTrigger);

const props = defineProps({
  experiences: {
    type: Array,
    required: true,
    default: () => [],
  },
});

// Tambahkan helper ini untuk merender markdown secara aman
const renderMarkdown = (text) => {
  if (!text) return "";
  return marked.parse(text, { breaks: true });
};

// Logic Sort: Terbaru di atas
const sortedExperiences = computed(() => {
  return [...props.experiences].sort((a, b) => {
    return new Date(b.start_date) - new Date(a.start_date);
  });
});

// Helper Format Tanggal
function formatDate(dateString) {
  if (!dateString) return "PRESENT";
  const date = new Date(dateString);
  return new Intl.DateTimeFormat("en-US", { month: "short", year: "numeric" }).format(date).toUpperCase();
}

// Helper Durasi (Versi Lebih Akurat)
function getDuration(start, end) {
  const startDate = new Date(start);
  const endDate = end ? new Date(end) : new Date();

  // Hitung total bulan
  let months = (endDate.getFullYear() - startDate.getFullYear()) * 12;
  months -= startDate.getMonth();
  months += endDate.getMonth();

  // Koreksi hari
  if (endDate.getDate() < startDate.getDate()) {
    months--;
  }

  if (months < 0) months = 0;

  const y = Math.floor(months / 12);
  const m = months % 12;

  if (y > 0 && m > 0) return `${y} Yrs ${m} Mos`;
  if (y > 0) return `${y} Yrs`;
  if (m === 0) return `Less than a month`;
  return `${m} Mos`;
}

// --- ANIMATION LOGIC ---
const sectionRef = ref(null);

onMounted(async () => {
  await nextTick();

  // 1. Header Animation (Judul & Deskripsi)
  gsap.fromTo(
    ".journey-header",
    { y: 50, opacity: 0 },
    {
      scrollTrigger: {
        trigger: sectionRef.value,
        start: "top 60%",
        // TAMBAHAN: Animasi hanya berjalan 1 kali
        once: true,
      },
      y: 0,
      opacity: 1,
      duration: 0.8,
      stagger: 0.2,
      ease: "power2.out",
    },
  );

  // 2. The Line Animation (Menggambar garis ke bawah sesuai scroll)
  // Tidak perlu toggleActions karena sudah pakai 'scrub: 1'
  gsap.fromTo(
    ".journey-line",
    { scaleY: 0 },
    {
      scaleY: 1,
      transformOrigin: "top center",
      ease: "none",
      scrollTrigger: {
        trigger: ".experience-list",
        start: "top 60%",
        end: "bottom 90%",
        scrub: 1,
      },
    },
  );

  // 3. Items Animation (Dot & Content)
  const items = gsap.utils.toArray(".experience-item");

  items.forEach((item) => {
    // Animasi Titik (Dot) - Pop up Elastis
    const dot = item.querySelector(".timeline-dot");
    gsap.fromTo(
      dot,
      { scale: 0 },
      {
        scale: 1,
        duration: 0.6,
        ease: "elastic.out(1, 0.5)",
        scrollTrigger: {
          trigger: item,
          start: "top 75%",
          // TAMBAHAN: Animasi dot hanya berjalan 1 kali
          once: true,
        },
      },
    );

    // Animasi Kartu & Tanggal (Fade Up)
    const content = item.querySelectorAll(".experience-content, .experience-date");
    gsap.fromTo(
      content,
      { y: 50, opacity: 0 },
      {
        y: 0,
        opacity: 1,
        duration: 0.8,
        stagger: 0.1,
        ease: "power2.out",
        scrollTrigger: {
          trigger: item,
          start: "top 75%",
          // TAMBAHAN: Animasi konten hanya berjalan 1 kali
          once: true,
        },
      },
    );
  });
});

onUnmounted(() => {
  ScrollTrigger.getAll().forEach((t) => t.kill());
});
</script>

<template>
  <section ref="sectionRef" class="py-20 bg-white overflow-hidden">
    <div class="max-w-5xl mx-auto px-4 md:px-6">
      <div class="text-center mb-12">
        <div class="journey-header inline-block">
          <h2
            class="text-4xl font-black text-black mb-6 font-serif tracking-wider inline-block relative border-b border-black/20 pb-2">
            <span class="relative z-10">My Journey</span>
          </h2>
        </div>
        <p
          class="journey-header mt-4 font-sans text-gray-500 text-sm md:text-base lowercase tracking-tight max-w-xl mx-auto">
          professional trajectory. executing solutions and refining architectures. continuous integration of practical
          experience into technical mastery.
        </p>
      </div>

      <div v-if="props.experiences.length === 0" class="text-center py-12 border border-dashed border-gray-300">
        <p class="font-mono text-gray-400">No experience data found.</p>
      </div>

      <div v-else class="experience-list relative">
        <div
          class="journey-line absolute left-4 md:left-1/2 top-0 bottom-0 w-[1px] md:-ml-[0.5px] bg-black/20 dark:bg-white/20 z-0 origin-top">
        </div>

        <div class="relative z-10 mb-12 flex items-center md:justify-center pl-4 md:pl-0">
          <div
            class="journey-header bg-white dark:!bg-[#ffffff] text-black dark:!text-black font-bold px-4 py-1 uppercase text-sm border border-black/20 shadow-sm animate-pulse-slow">
            NOW / FUTURE
          </div>
        </div>

        <div class="space-y-12 md:space-y-0">
          <div v-for="(exp, index) in sortedExperiences" :key="exp.id"
            class="experience-item relative flex flex-col md:flex-row items-start group"
            :class="index % 2 === 0 ? 'md:flex-row-reverse' : ''">
            <div
              class="timeline-dot absolute left-4 md:left-1/2 w-3 h-3 -ml-[5px] md:-ml-[6px] bg-white dark:!bg-[#ffffff] border-2 border-black/40 dark:!border-[#ffffff] rounded-full z-20 top-8 group-hover:scale-150 group-hover:border-black group-hover:bg-black transition-all duration-300">
            </div>

            <div class="experience-date hidden md:block w-1/2 px-10 pt-6"
              :class="index % 2 === 0 ? 'text-left' : 'text-right'">
              <div class="font-black text-2xl uppercase italic">{{ formatDate(exp.start_date) }}</div>
              <div class="font-mono text-gray-500 font-bold text-sm">
                {{ exp.end_date ? formatDate(exp.end_date) : "PRESENT" }}
              </div>
              <div
                class="mt-2 inline-block bg-gray-50 border border-black/10 px-2 py-0.5 text-xs font-bold font-mono">
                {{ getDuration(exp.start_date, exp.end_date) }}
              </div>
            </div>

            <div class="w-full md:w-1/2 pl-12 md:pl-0 pr-0 md:px-10">
              <div
                class="experience-content relative bg-white border border-black/10 p-6 shadow-[0_4px_20px_rgba(0,0,0,0.04)] group-hover:shadow-[0_8px_30px_rgba(0,0,0,0.08)] group-hover:border-black/20 group-hover:-translate-y-1 transition-all duration-300">
                <div class="md:hidden mb-4 border-b border-dashed border-gray-200 pb-2">
                  <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span class="bg-black text-white text-xs font-bold px-2 py-1 uppercase">
                      {{ formatDate(exp.start_date) }} — {{ exp.end_date ? formatDate(exp.end_date) : "NOW" }}
                    </span>
                  </div>
                  <span
                    class="inline-block bg-[#E7E7E7] dark:!bg-white border border-black px-2 py-0.5 text-[10px] font-bold font-mono dark:!text-black">
                    {{ getDuration(exp.start_date, exp.end_date) }}
                  </span>
                </div>

                <h3 class="text-xl md:text-2xl font-black uppercase leading-tight mb-2">
                  {{ exp.role }}
                </h3>

                <div class="flex flex-wrap items-center gap-2 mb-4">
                  <span
                    class="text-black font-bold flex items-center gap-1.5 border-b border-transparent hover:border-black/40 transition-colors">
                    <Icon :icon="exp.status === 'Education' ? 'lucide:graduation-cap' : 'lucide:building-2'"
                      class="w-4 h-4" />
                    {{ exp.company_name }}
                  </span>

                  <span
                    class="text-[10px] font-black font-mono border border-black/10 px-2 py-0.5 bg-gray-50 uppercase text-gray-600">
                    {{ exp.status }}
                  </span>
                </div>

                <div v-if="exp.location" class="flex items-center gap-2 text-xs font-bold text-gray-600 uppercase mb-4">
                  <Icon icon="lucide:map-pin" class="text-black w-3.5 h-3.5" />
                  {{ exp.location }}
                </div>

                <div v-html="renderMarkdown(exp.description)"
                  class="markdown-content font-mono text-sm leading-relaxed text-gray-700 mb-6"></div>

                <div v-if="!exp.end_date" class="absolute -top-3 -right-3 rotate-3">
                  <span class="bg-gray-50 border border-black/10 px-3 py-1 text-xs font-black uppercase shadow-sm">
                    {{ exp.status === "Education" ? "Ongoing" : "Current" }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="relative z-10 mt-12 flex items-center md:justify-center pl-4 md:pl-0">
          <div
            class="journey-header bg-white dark:!bg-[#ffffff] text-black dark:!text-black font-black px-4 py-2 uppercase text-sm border border-black/20 flex items-center gap-2 shadow-sm hover:shadow-md transition-shadow">
            <Icon icon="lucide:flag" />
            START JOURNEY
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Style agar Markdown tetap rapi dan sesuai tema lo-fi/neubrutalism */
.markdown-content :deep(ul) {
  list-style-type: disc;
  margin-left: 1.5rem;
  margin-bottom: 1rem;
}

.markdown-content :deep(li) {
  margin-bottom: 0.25rem;
}

.markdown-content :deep(strong) {
  font-weight: 900;
  color: black;
}

.markdown-content :deep(p) {
  margin-bottom: 1rem;
}

.markdown-content :deep(a) {
  text-decoration: underline;
  font-weight: bold;
}
</style>