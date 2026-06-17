<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBlogRequest;
use App\Http\Requests\UpdateBlogRequest;
use App\Models\Blog;
use Illuminate\Http\Request;
use App\Traits\ImageUploadTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BlogController extends Controller
{
    use ImageUploadTrait;

    // PUBLIK
    public function indexPublic()
    {
        // Hanya yang published
        $blogs = Blog::where('is_published', true)->latest()->get();
        return response()->json($blogs);
    }

    public function showPublic($slug)
    {
        $blog = Blog::where('slug', $slug)->where('is_published', true)->firstOrFail();
        return response()->json($blog);
    }

    // ADMIN
    public function indexAdmin()
    {
        $blogs = Blog::latest()->get();
        return response()->json($blogs);
    }

    public function showAdmin($id)
    {
        $blog = Blog::findOrFail($id);
        return response()->json($blog);
    }

    public function store(StoreBlogRequest $request)
    {
        $data = $request->validated();
        $blog = Blog::create($data);

        return response()->json([
            'message' => 'Blog created successfully',
            'data' => $blog
        ], 201);
    }

    public function update(UpdateBlogRequest $request, $id)
    {
        $blog = Blog::findOrFail($id);
        $data = $request->validated();
        
        $blog->update($data);

        return response()->json([
            'message' => 'Blog updated successfully',
            'data' => $blog
        ]);
    }

    public function destroy($id)
    {
        $blog = Blog::findOrFail($id);
        $blog->delete();

        return response()->json([
            'message' => 'Blog deleted successfully'
        ]);
    }

    // TIPTAP IMAGE UPLOAD
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120' // max 5MB
        ]);

        $imageUrl = $this->handleFileUpload(
            $request->file('image'),
            'blogs_inline'
        );

        $finalUrl = $this->resolveUrl($imageUrl);

        return response()->json([
            'url' => $finalUrl
        ]);
    }

    private function resolveUrl($path)
    {
        if (empty($path)) return null;
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        return url(Storage::url($path));
    }
}
