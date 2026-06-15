<script setup>
import { ref, onMounted, computed } from "vue";
import { getAllBlogsPublic } from "../../lib/api/BlogApi";
import { useRouter } from "vue-router";
import NProgress from "nprogress";
import "nprogress/nprogress.css";

const blogs = ref([]);
const isLoading = ref(true);
const router = useRouter();

const groupedBlogs = computed(() => {
  const groups = {};
  blogs.value.forEach((blog) => {
    const year = new Date(blog.created_at).getFullYear();
    if (!groups[year]) groups[year] = [];
    groups[year].push(blog);
  });

  // Sort descending years
  const sortedKeys = Object.keys(groups).sort((a, b) => b - a);
  const result = [];
  sortedKeys.forEach((year) => {
    result.push({ year, blogs: groups[year] });
  });
  return result;
});

const formatDate = (dateString) => {
  const date = new Date(dateString);
  const month = date.toLocaleString("en-US", { month: "short" });
  const day = date.getDate();
  return `${month.toLowerCase()} ${day}`;
};

onMounted(async () => {
  try {
    const response = await getAllBlogsPublic();
    blogs.value = await response.json();
  } catch (error) {
    console.error("Failed to load blogs", error);
  } finally {
    isLoading.value = false;
    NProgress.done();
  }
});
</script>

<template>
  <div class="pt-16 md:pt-36 pb-16 min-h-screen bg-white dark:bg-black flex flex-col items-center">
    <div class="w-full max-w-3xl px-4 md:px-8">
      <!-- Empty State -->
      <div v-if="!isLoading && blogs.length === 0" class="text-center font-sans text-neutral-500 py-12">
        no article yet!
      </div>

      <!-- Blog List by Year -->
      <div v-else-if="!isLoading && blogs.length > 0" class="w-full flex flex-col gap-24">
        <div v-for="group in groupedBlogs" :key="group.year" class="relative w-full mt-10 md:mt-16">
          <!-- Background Year (Hollow Text) -->
          <div
            class="absolute top-0 -left-2 md:-left-4 -translate-y-6 md:-translate-y-15 text-[4rem] md:text-[8rem] font-black select-none z-0 leading-none pointer-events-none"
            style="color: transparent; -webkit-text-stroke: 1px rgba(150, 150, 150, 0.15)">
            {{ group.year }}
          </div>

          <!-- Articles List -->
          <div class="relative z-10 w-full flex flex-col items-start px-2 md:px-8">
            <router-link
              v-for="blog in group.blogs"
              :key="blog.id"
              :to="'/blogs/' + blog.slug"
              class="blog-row cursor-pointer group flex flex-col sm:flex-row justify-start items-start sm:items-center w-full py-2 transition-colors gap-3 md:gap-4">
              <h2
                class="text-lg md:text-xl font-small font-sans text-neutral-600 dark:text-neutral-400 group-hover:text-neutral-900 dark:group-hover:text-white transition-colors leading-tight">
                {{ blog.title }}
              </h2>

              <div
                class="flex items-center gap-2 mt-1 sm:mt-0 text-sm font-sans text-neutral-400 dark:text-neutral-500 whitespace-nowrap group-hover:text-neutral-600 dark:group-hover:text-neutral-300 transition-colors">
                <span>{{ formatDate(blog.created_at) }}</span>
                <span class="text-[10px]">•</span>
                <span>{{ blog.read_time }} min</span>
              </div>
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
