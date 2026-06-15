<script setup>
import { ref, onMounted, nextTick } from "vue";
import { useRoute, useRouter } from "vue-router";
import { getSingleBlogPublic } from "../../lib/api/BlogApi";
import { Icon } from "@iconify/vue";
import NProgress from "nprogress";
import "nprogress/nprogress.css";

const route = useRoute();
const router = useRouter();
const blog = ref(null);
const isLoading = ref(true);

const toc = ref([]);
const showToc = ref(false);
const contentRef = ref(null);
let hideTimeout = null;

const onMouseEnter = () => {
  if (hideTimeout) clearTimeout(hideTimeout);
  showToc.value = true;
};

const onMouseLeave = () => {
  hideTimeout = setTimeout(() => {
    showToc.value = false;
  }, 150);
};

const extractToc = () => {
  if (!contentRef.value) return;
  const headings = contentRef.value.querySelectorAll("h2, h3");
  const extractedToc = [];

  headings.forEach((el, index) => {
    let id = el.id;
    if (!id) {
      id =
        el.innerText
          .toLowerCase()
          .replace(/\s+/g, "-")
          .replace(/[^\w-]/g, "") || `heading-${index}`;
      el.id = id;
    }
    extractedToc.push({
      id,
      text: el.innerText,
      level: el.tagName.toLowerCase(),
    });
  });

  toc.value = extractedToc;
};

const scrollToHeading = (id) => {
  const el = document.getElementById(id);
  if (el) {
    const y = el.getBoundingClientRect().top + window.scrollY - 100;
    window.scrollTo({ top: y, behavior: "smooth" });
  }
};

onMounted(async () => {
  try {
    const response = await getSingleBlogPublic(route.params.slug);
    if (!response.ok) throw new Error("Not found");
    blog.value = await response.json();

    // Set isLoading to false FIRST so the v-if renders the DOM
    isLoading.value = false;

    await nextTick();
    extractToc();
  } catch (error) {
    router.push("/404");
    isLoading.value = false;
  } finally {
    NProgress.done();
  }
});
</script>

<template>
  <div class="pt-20 pb-20 min-h-screen bg-white dark:bg-black text-neutral-800 dark:text-neutral-300">
    <!-- Desktop ToC Sidebar -->
    <transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 -translate-x-4"
      enter-to-class="opacity-100 translate-x-0"
      leave-active-class="transition-all duration-300 ease-in"
      leave-from-class="opacity-100 translate-x-0"
      leave-to-class="opacity-0 -translate-x-4">
      <div
        v-show="showToc"
        class="hidden lg:block fixed left-0 top-0 w-[calc(50%-24rem)] h-full z-40 pointer-events-auto"
        @mouseenter="onMouseEnter"
        @mouseleave="onMouseLeave">
        <div class="absolute left-0 top-34 pl-7 xl:pl-10">
          <ul
            class="flex flex-col gap-3 text-sm font-sans border-l-2 border-neutral-200 dark:border-neutral-800 pl-4 w-48 xl:w-64 select-none">
            <li v-for="item in toc" :key="item.id" class="w-fit" :class="item.level === 'h3' ? 'ml-4 text-xs' : ''">
              <a
                :href="'#' + item.id"
                @click.prevent="scrollToHeading(item.id)"
                class="cursor-pointer border-b border-neutral-300 dark:border-neutral-700 hover:border-black dark:hover:border-white text-neutral-500 dark:text-neutral-400 hover:text-black dark:hover:text-white transition-all leading-snug">
                {{ item.text }}
              </a>
            </li>
          </ul>
        </div>
      </div>
    </transition>

    <div class="max-w-3xl mx-auto px-4 md:px-8">
      <article v-if="!isLoading && blog" @mouseenter="onMouseEnter" @mouseleave="onMouseLeave" class="relative mt-8">
        <!-- Header -->
        <header class="mb-12">
          <h1 class="text-3xl md:text-5xl font-medium font-sans leading-tight mb-4 text-black dark:text-white">
            {{ blog.title }}
          </h1>

          <div class="flex flex-wrap items-center gap-2 text-sm font-sans text-neutral-500 dark:text-neutral-400">
            <span>
              {{
                new Date(blog.created_at).toLocaleDateString("en-US", {
                  month: "short",
                  day: "numeric",
                  year: "numeric",
                })
              }}
            </span>
            <span>•</span>
            <span>{{ blog.read_time }} min</span>
          </div>
        </header>

        <!-- Content (Tiptap HTML) -->
        <div
          ref="contentRef"
          class="prose prose-neutral dark:prose-invert prose-lg max-w-none font-sans prose-headings:font-black prose-headings:text-black dark:prose-headings:text-white prose-a:text-neutral-600 dark:prose-a:text-neutral-400 hover:prose-a:text-black dark:hover:prose-a:text-white prose-a:transition-colors prose-img:rounded-lg prose-img:mx-auto"
          v-html="blog.content"></div>

        <!-- Back Button at bottom -->
        <router-link
          to="/blogs"
          class="cursor-pointer mt-16 font-sans font-medium text-neutral-500 hover:text-black dark:hover:text-white border-b border-neutral-300 dark:border-neutral-700 hover:border-black dark:hover:border-white transition-all flex items-center gap-2 w-fit">
          <span class="font-bold">></span>
          cd . .
        </router-link>
      </article>
    </div>
  </div>
</template>

<style>
/* Menjaga enter/baris baru yang kosong agar tidak hilang karena CSS collapse */
.prose p:empty::before {
  content: "\00a0";
  display: inline-block;
}

/* Hover simbol # pada judul konten */
.prose h2,
.prose h3 {
  position: relative;
}
.prose h2::before,
.prose h3::before {
  content: "#";
  position: absolute;
  left: -1em;
  opacity: 0;
  color: #a3a3a3; /* Warna abu-abu netral */
  transition: opacity 0.2s ease-in-out;
}
.prose h2:hover::before,
.prose h3:hover::before {
  opacity: 1;
}
</style>
