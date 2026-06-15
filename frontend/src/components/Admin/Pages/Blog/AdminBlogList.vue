<script setup>
import { ref, onMounted } from "vue";
import Swal from "sweetalert2";
import { Icon } from "@iconify/vue";
import { getAllBlogsAdmin, deleteBlog } from "../../../../lib/api/BlogApi";
import { useRouter } from "vue-router";

const router = useRouter();
const blogs = ref([]);
const isLoading = ref(false);

const fetchBlogs = async () => {
  isLoading.value = true;
  try {
    const response = await getAllBlogsAdmin();
    const data = await response.json();
    blogs.value = data;
  } catch (error) {
    Swal.fire("Error", "Gagal mengambil data blogs", "error");
  } finally {
    isLoading.value = false;
  }
};

const handleDelete = async (id) => {
  const result = await Swal.fire({
    title: "Yakin Hapus?",
    text: "Artikel ini akan dihapus permanen!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#000",
    cancelButtonColor: "#d33",
    confirmButtonText: "Ya, Hapus!",
    customClass: { popup: "border-4 border-black rounded-none" },
  });

  if (result.isConfirmed) {
    try {
      const response = await deleteBlog(id);
      if (!response.ok) throw new Error("Gagal menghapus");

      Swal.fire({
        icon: "success",
        title: "Terhapus!",
        text: "Artikel berhasil dihapus.",
        timer: 1500,
        showConfirmButton: false,
      });
      fetchBlogs();
    } catch (error) {
      Swal.fire("Error", "Terjadi kesalahan", "error");
    }
  }
};

onMounted(() => {
  fetchBlogs();
});
</script>

<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center border-b-4 border-black pb-4">
      <h1 class="text-3xl font-black tracking-tighter uppercase">Manage Blogs</h1>
      <router-link
        to="/admin/dashboard/blogs/form"
        class="bg-black text-white px-6 py-2 font-bold font-mono border-4 border-black hover:bg-white hover:text-black transition-colors flex items-center gap-2 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
        <Icon icon="lucide:plus" width="20" height="20" />
        Write Blog
      </router-link>
    </div>

    <div v-if="isLoading" class="text-center p-8 font-mono font-bold">Loading...</div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">
      <div
        v-if="blogs.length === 0"
        class="col-span-full text-center p-8 border-4 border-black bg-white shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] font-mono">
        Belum ada artikel. Klik "Write Blog" untuk mulai menulis!
      </div>

      <div
        v-for="blog in blogs"
        :key="blog.id"
        class="border-4 border-black bg-white p-5 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] flex flex-col relative group">
        <div class="flex justify-between items-start mb-4">
          <div
            :class="[
              blog.is_published ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800',
              'px-2 py-1 text-xs font-bold font-mono border-2 border-black inline-block',
            ]">
            {{ blog.is_published ? "Published" : "Draft" }}
          </div>
          <div class="flex gap-2">
            <button
              @click="router.push(`/admin/dashboard/blogs/form/${blog.id}`)"
              class="p-2 bg-yellow-400 border-2 border-black hover:bg-yellow-500 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] transition-transform hover:scale-105"
              title="Edit">
              <Icon icon="lucide:edit" />
            </button>
            <button
              @click="handleDelete(blog.id)"
              class="p-2 bg-red-500 text-white border-2 border-black hover:bg-red-600 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] transition-transform hover:scale-105"
              title="Delete">
              <Icon icon="lucide:trash-2" />
            </button>
          </div>
        </div>

        <h2 class="text-xl font-bold font-serif mb-2 line-clamp-2 leading-tight">{{ blog.title }}</h2>

        <div class="mt-auto pt-4 flex gap-4 text-xs font-mono text-gray-600 border-t-2 border-dashed border-gray-300">
          <span class="flex items-center gap-1">
            <Icon icon="lucide:clock" />
            {{ blog.read_time }} min read
          </span>
          <span class="flex items-center gap-1">
            <Icon icon="lucide:calendar" />
            {{ new Date(blog.created_at).toLocaleDateString() }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
