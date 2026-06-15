<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { useRoute, useRouter } from "vue-router";
import Swal from "sweetalert2";
import { getSingleBlogAdmin, createBlog, updateBlog, uploadBlogImage } from "../../../../lib/api/BlogApi";
import { Editor, EditorContent } from "@tiptap/vue-3";
import StarterKit from "@tiptap/starter-kit";
import ImageResize from "tiptap-extension-resize-image";
import TextAlign from "@tiptap/extension-text-align";
import { Icon } from "@iconify/vue";

const route = useRoute();
const router = useRouter();
const isEditing = ref(false);
const isLoading = ref(false);
const isSaving = ref(false);
const isPreviewMode = ref(false);

const form = ref({
  title: "",
  content: "",
  is_published: true,
});

let editor = null;
const imageInput = ref(null);

onMounted(async () => {
  if (route.params.id) {
    isEditing.value = true;
    isLoading.value = true;
    try {
      const response = await getSingleBlogAdmin(route.params.id);
      const data = await response.json();
      form.value.title = data.title;
      form.value.content = data.content;
      form.value.is_published = data.is_published;
    } catch (e) {
      Swal.fire("Error", "Gagal memuat data blog", "error");
      router.push("/admin/dashboard/blogs");
    } finally {
      isLoading.value = false;
    }
  }

  editor = new Editor({
    content: form.value.content,
    extensions: [
      StarterKit,
      TextAlign.configure({
        types: ['heading', 'paragraph'],
      }),
      ImageResize.configure({
        inline: true,
        allowBase64: true,
      }),
    ],
    onUpdate: () => {
      form.value.content = editor.getHTML();
    },
    editorProps: {
      attributes: {
        class: "prose max-w-none min-h-[300px] outline-none p-4 font-serif",
      },
    },
  });
});

onBeforeUnmount(() => {
  if (editor) {
    editor.destroy();
  }
});

const triggerImageUpload = () => {
  if (imageInput.value) {
    imageInput.value.click();
  }
};

const handleImageUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  // Tampilkan loading state atau toast (opsional)
  Swal.fire({
    title: "Uploading image...",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  const formData = new FormData();
  formData.append("image", file);

  try {
    const response = await uploadBlogImage(formData);
    const data = await response.json();

    if (response.ok && data.url) {
      editor.chain().focus().setImage({ src: data.url }).run();
      Swal.close();
    } else {
      throw new Error("Gagal mendapatkan URL gambar");
    }
  } catch (err) {
    Swal.fire("Error", "Gagal mengunggah gambar", "error");
  }

  // Reset input file
  if (imageInput.value) imageInput.value.value = "";
};

const saveBlog = async () => {
  if (!form.value.title || !form.value.content) {
    Swal.fire("Peringatan", "Judul dan konten tidak boleh kosong", "warning");
    return;
  }

  isSaving.value = true;
  try {
    let response;
    if (isEditing.value) {
      response = await updateBlog(route.params.id, form.value);
    } else {
      response = await createBlog(form.value);
    }

    if (response.ok) {
      Swal.fire({
        icon: "success",
        title: "Berhasil!",
        text: isEditing.value ? "Blog diperbarui" : "Blog ditambahkan",
        timer: 1500,
        showConfirmButton: false,
      });
      router.push("/admin/dashboard/blogs");
    } else {
      throw new Error("Gagal menyimpan data");
    }
  } catch (error) {
    Swal.fire("Error", "Terjadi kesalahan saat menyimpan", "error");
  } finally {
    isSaving.value = false;
  }
};
</script>

<template>
  <div class="space-y-6 w-full max-w-6xl mx-auto">
    <div class="flex justify-between items-center border-b-4 border-black pb-4">
      <h1 class="text-3xl font-black tracking-tighter uppercase">{{ isEditing ? "Edit" : "Write" }} Blog</h1>
      <button
        @click="router.push('/admin/dashboard/blogs')"
        class="bg-gray-200 text-black px-4 py-2 font-bold font-mono border-4 border-black hover:bg-black hover:text-white transition-colors">
        Back
      </button>
    </div>

    <div v-if="isLoading" class="text-center font-mono py-8">Loading data...</div>

    <form
      v-else-if="!isPreviewMode"
      @submit.prevent="saveBlog"
      class="bg-white border-4 border-black p-6 shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] space-y-6">
      <!-- Title -->
      <div>
        <label class="block font-bold font-mono mb-2 uppercase text-sm">Blog Title</label>
        <input
          v-model="form.title"
          type="text"
          placeholder="Cara Belajar Coding dengan Seru..."
          class="w-full p-3 border-4 border-black font-sans text-lg focus:outline-none focus:ring-4 focus:ring-gray-200 transition-all" />
      </div>

      <!-- Editor Toolbar -->
      <div>
        <label class="block font-bold font-mono mb-2 uppercase text-sm">Content</label>
        <div class="border-4 border-black mb-[-4px] relative z-10 bg-gray-100 flex flex-wrap gap-2 p-2" v-if="editor">
          <button
            type="button"
            @click="editor.chain().focus().toggleBold().run()"
            :class="{ 'bg-black text-white': editor.isActive('bold') }"
            class="p-2 border-2 border-transparent hover:border-black transition-colors rounded">
            <Icon icon="lucide:bold" />
          </button>
          <button
            type="button"
            @click="editor.chain().focus().toggleItalic().run()"
            :class="{ 'bg-black text-white': editor.isActive('italic') }"
            class="p-2 border-2 border-transparent hover:border-black transition-colors rounded">
            <Icon icon="lucide:italic" />
          </button>
          <button
            type="button"
            @click="editor.chain().focus().toggleStrike().run()"
            :class="{ 'bg-black text-white': editor.isActive('strike') }"
            class="p-2 border-2 border-transparent hover:border-black transition-colors rounded">
            <Icon icon="lucide:strikethrough" />
          </button>
          <div class="w-px h-6 bg-gray-400 my-auto mx-2"></div>
          <button
            type="button"
            @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
            :class="{ 'bg-black text-white': editor.isActive('heading', { level: 2 }) }"
            class="p-2 border-2 border-transparent hover:border-black transition-colors rounded font-bold font-serif">
            H2
          </button>
          <button
            type="button"
            @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
            :class="{ 'bg-black text-white': editor.isActive('heading', { level: 3 }) }"
            class="p-2 border-2 border-transparent hover:border-black transition-colors rounded font-bold font-serif">
            H3
          </button>
          <button
            type="button"
            @click="editor.chain().focus().toggleBulletList().run()"
            :class="{ 'bg-black text-white': editor.isActive('bulletList') }"
            class="p-2 border-2 border-transparent hover:border-black transition-colors rounded">
            <Icon icon="lucide:list" />
          </button>
          <button
            type="button"
            @click="editor.chain().focus().toggleOrderedList().run()"
            :class="{ 'bg-black text-white': editor.isActive('orderedList') }"
            class="p-2 border-2 border-transparent hover:border-black transition-colors rounded">
            <Icon icon="lucide:list-ordered" />
          </button>
          <button
            type="button"
            @click="editor.chain().focus().toggleBlockquote().run()"
            :class="{ 'bg-black text-white': editor.isActive('blockquote') }"
            class="p-2 border-2 border-transparent hover:border-black transition-colors rounded">
            <Icon icon="lucide:quote" />
          </button>
          <div class="w-px h-6 bg-gray-400 my-auto mx-2"></div>
          
          <!-- Text Align Buttons -->
          <button
            type="button"
            @click="editor.chain().focus().setTextAlign('left').run()"
            :class="{ 'bg-black text-white': editor.isActive({ textAlign: 'left' }) }"
            class="p-2 border-2 border-transparent hover:border-black transition-colors rounded">
            <Icon icon="lucide:align-left" />
          </button>
          <button
            type="button"
            @click="editor.chain().focus().setTextAlign('center').run()"
            :class="{ 'bg-black text-white': editor.isActive({ textAlign: 'center' }) }"
            class="p-2 border-2 border-transparent hover:border-black transition-colors rounded">
            <Icon icon="lucide:align-center" />
          </button>
          <button
            type="button"
            @click="editor.chain().focus().setTextAlign('right').run()"
            :class="{ 'bg-black text-white': editor.isActive({ textAlign: 'right' }) }"
            class="p-2 border-2 border-transparent hover:border-black transition-colors rounded">
            <Icon icon="lucide:align-right" />
          </button>
          <div class="w-px h-6 bg-gray-400 my-auto mx-2"></div>

          <!-- Image Upload Button -->
          <button
            type="button"
            @click="triggerImageUpload"
            class="p-2 border-2 border-transparent hover:border-black transition-colors rounded text-blue-600 hover:bg-blue-50"
            title="Insert Image">
            <Icon icon="lucide:image-plus" />
          </button>
          <input type="file" ref="imageInput" @change="handleImageUpload" accept="image/*" class="hidden" />
        </div>

        <!-- Editor Content -->
        <div class="border-4 border-black bg-white">
          <editor-content :editor="editor" />
        </div>
      </div>

      <!-- Status & Submit -->
      <div
        class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pt-4 border-t-4 border-black">
        <label class="flex items-center gap-3 cursor-pointer">
          <div class="relative">
            <input type="checkbox" v-model="form.is_published" class="sr-only" />
            <div
              :class="[
                form.is_published ? 'bg-black' : 'bg-gray-300',
                'block w-14 h-8 transition-colors border-2 border-black',
              ]"></div>
            <div
              :class="[
                form.is_published ? 'translate-x-6' : 'translate-x-0',
                'dot absolute left-1 top-1 bg-white w-6 h-6 transition-transform border-2 border-black',
              ]"></div>
          </div>
          <span class="font-bold font-mono">{{ form.is_published ? "Publish immediately" : "Save as Draft" }}</span>
        </label>

        <button
          type="button"
          @click="isPreviewMode = true"
          class="bg-black text-white px-8 py-3 font-black tracking-widest uppercase border-4 border-black hover:bg-white hover:text-black transition-colors shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
          Preview Blog
        </button>
      </div>
    </form>

    <div v-else-if="isPreviewMode" class="bg-white border-4 border-black p-6 md:p-12 shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] space-y-8">
      <article class="relative mt-8">
        <!-- Title and Meta from SingleBlog -->
        <header class="mb-12">
          <h1 class="text-3xl md:text-5xl font-medium font-sans leading-tight mb-4 text-black">
            {{ form.title || 'Untitled Blog' }}
          </h1>
          <div class="flex flex-wrap items-center gap-2 text-sm font-sans text-neutral-500">
            <span>
              {{ new Date().toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" }) }}
            </span>
            <span>—</span>
            <span>~ min read</span>
          </div>
        </header>

        <!-- Content (Tiptap HTML) -->
        <div class="prose prose-neutral prose-lg max-w-none font-sans prose-headings:font-black prose-headings:text-black prose-a:text-neutral-600 hover:prose-a:text-black prose-a:transition-colors prose-img:rounded-lg prose-img:mx-auto" v-html="form.content"></div>
      </article>
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pt-8 border-t-4 border-black mt-12">
        <button
          type="button"
          @click="isPreviewMode = false"
          class="bg-gray-200 text-black px-8 py-3 font-black tracking-widest uppercase border-4 border-black hover:bg-black hover:text-white transition-colors shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
          Back to Edit
        </button>
        <button
          type="button"
          @click="saveBlog"
          :disabled="isSaving"
          class="bg-black text-white px-8 py-3 font-black tracking-widest uppercase border-4 border-black hover:bg-white hover:text-black transition-colors shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] disabled:opacity-50 disabled:cursor-not-allowed">
          {{ isSaving ? "Saving..." : "Save Blog" }}
        </button>
      </div>
    </div>
  </div>
</template>

<style>
/* Styling minimal untuk konten Tiptap (mirip tailwind typography) */
.ProseMirror p {
  margin-bottom: 1em;
}
.ProseMirror h2 {
  font-size: 1.5em;
  font-weight: bold;
  margin-top: 1.5em;
  margin-bottom: 0.5em;
}
.ProseMirror h3 {
  font-size: 1.17em;
  font-weight: bold;
  margin-top: 1.2em;
  margin-bottom: 0.5em;
}
.ProseMirror ul {
  list-style-type: disc;
  padding-left: 1.5em;
  margin-bottom: 1em;
}
.ProseMirror ol {
  list-style-type: decimal;
  padding-left: 1.5em;
  margin-bottom: 1em;
}
.ProseMirror blockquote {
  border-left: 4px solid black;
  padding-left: 1em;
  margin-left: 0;
  font-style: italic;
}
.ProseMirror img,
.prose img {
  max-width: 100%;
  height: auto;
  border: 4px solid black;
}

/* Memastikan H2 dan H3 selalu bold di Preview Mode */
.prose h2 {
  font-size: 1.5em;
  font-weight: bold !important;
  margin-top: 1.5em;
  margin-bottom: 0.5em;
}
.prose h3 {
  font-size: 1.17em;
  font-weight: bold !important;
  margin-top: 1.2em;
  margin-bottom: 0.5em;
}

/* Menjaga enter/baris baru yang kosong tetap memiliki tinggi */
.ProseMirror p:empty::before,
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
