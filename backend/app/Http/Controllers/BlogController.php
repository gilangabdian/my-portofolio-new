<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBlogRequest;
use App\Http\Requests\UpdateBlogRequest;
use App\Models\Blog;
use Illuminate\Http\Request;
use Cloudinary\Configuration\Configuration;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BlogController extends Controller
{
    private function initCloudinary()
    {
        Configuration::instance([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key' => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => [
                'secure' => true,
            ],
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

    private function handleFileUpload($file, $folder, $oldPath, $disk, $uploadApi)
    {
        // Delete old file if exists
        if ($oldPath) {
            if ($disk === 'cloudinary' && str_starts_with($oldPath, 'http')) {
                // Extract public ID from Cloudinary URL
                preg_match('/upload\/(?:v\d+\/)?([^\.]+)/', $oldPath, $matches);
                if (isset($matches[1])) {
                    try {
                        $uploadApi->destroy($matches[1]);
                    } catch (\Exception $e) {
                        Log::error('Cloudinary delete failed: ' . $e->getMessage());
                    }
                }
            } elseif ($disk === 'local' && !str_starts_with($oldPath, 'http')) {
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        }

        // Upload new file
        if ($disk === 'cloudinary') {
            $result = $uploadApi->upload($file->getRealPath(), [
                'folder' => $folder,
                'resource_type' => 'image',
                'access_mode' => 'public',
                'overwrite' => true,
            ]);
            return $result['secure_url'];
        }

        return $file->store($folder, 'public');
    }

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

        $disk = config('filesystems.default', 'local');
        $uploadApi = null;

        if ($disk === 'cloudinary') {
            $this->initCloudinary();
            $uploadApi = new \Cloudinary\Api\Upload\UploadApi();
        }

        $imageUrl = $this->handleFileUpload(
            $request->file('image'),
            'blogs_inline', // folder
            null,
            $disk,
            $uploadApi
        );

        $finalUrl = $this->resolveUrl($imageUrl);

        return response()->json([
            'url' => $finalUrl
        ]);
    }
}
