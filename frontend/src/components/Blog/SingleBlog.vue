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

// Modal variables
const selectedImage = ref(null);

const openModal = (url) => {
  selectedImage.value = url;
};

const closeModal = () => {
  selectedImage.value = null;
};

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

    // Inject event listeners to all images rendered by v-html
    if (contentRef.value) {
      const images = contentRef.value.querySelectorAll("img");
      images.forEach((img) => {
        img.addEventListener("click", () => {
          openModal(img.src);
        });
      });
    }
  } catch (error) {
    router.push("/404");
    isLoading.value = false;
  } finally {
    NProgress.done();
  }
});
</script>

<template>
  <div class="pt-20 pb-20 min-h-screen bg-white dark:bg-black text-neutral-800 dark:text-neutral-300 font-[Inter]">
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
            class="flex flex-col gap-3 text-sm border-l-2 border-neutral-200 dark:border-neutral-800 pl-4 w-48 xl:w-64 select-none">
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
          <h1 class="text-3xl md:text-5xl font-medium leading-none mb-3 text-black dark:text-white">
            {{ blog.title }}
          </h1>

          <div class="flex flex-wrap items-center gap-2 text-sm text-neutral-500 dark:text-neutral-500">
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
          class="prose prose-neutral dark:prose-invert prose-lg max-w-none font-[Inter] prose-headings:font-black prose-headings:text-black dark:prose-headings:text-white prose-img:rounded-lg"
          v-html="blog.content"></div>

        <!-- Back Button at bottom -->
        <router-link
          to="/blogs"
          class="group cursor-pointer mt-16 font-medium text-neutral-500 flex items-center gap-2 w-fit">
          <span class="font-mono">></span>
          <span
            class="border-b border-neutral-300 dark:border-neutral-700 group-hover:border-black dark:group-hover:border-white group-hover:text-black dark:group-hover:text-white transition-all pb-[1px]">
            cd . .
          </span>
        </router-link>
      </article>
    </div>

    <!-- Modal Image Zoom (Like Artworks) -->
    <div
      v-if="selectedImage"
      class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 backdrop-blur-sm"
      @click="closeModal">
      <img :src="selectedImage" class="w-full h-full object-contain" alt="Enlarged Blog Image" />
    </div>
  </div>
</template>

<style>
/* Menjaga enter/baris baru yang kosong agar tidak hilang karena CSS collapse */
.prose p:empty::before {
  content: "\00a0";
  display: inline-block;
}

/* Custom Paragraph Styling */
.prose p {
  color: #52525b !important; /* text-neutral-600 */
}
.dark .prose p {
  color: #a1a1aa !important; /* text-neutral-400 */
}

/* Custom Link Styling */
.prose a {
  font-weight: 600 !important;
  color: #000000 !important; /* text-neutral-900 */
  text-decoration: underline !important;
  text-decoration-color: #d4d4d8 !important; /* neutral-300 */
  text-underline-offset: 2px !important;
  transition: all 0.2s ease-in-out;
}
.dark .prose a {
  color: #e5e5e5 !important; /* text-neutral-200 */
  text-decoration-color: #3f3f46 !important; /* neutral-700 */
}
.prose a:hover {
  text-decoration-color: #171717 !important; /* neutral-900 */
}
.dark .prose a:hover {
  text-decoration-color: #e5e5e5 !important; /* neutral-200 */
}

/* Memastikan foto menjadi Block (1 foto per baris) sejajar dengan perbaikan di Admin */
.prose img {
  display: block;
  margin: 1.5em auto; /* Jarak atas bawah normal, posisi ke tengah */
  max-width: 100%;
  height: auto;
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
