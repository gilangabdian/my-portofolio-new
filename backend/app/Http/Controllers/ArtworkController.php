<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArtworkRequest;
use App\Http\Requests\UpdateArtworkRequest;
use App\Models\Artwork;
use Cloudinary\Configuration\Configuration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ArtworkController extends Controller
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

    public function index()
    {
        $artworks = Artwork::latest()->get();
        
        foreach ($artworks as $artwork) {
            $artwork->image_url = $this->resolveUrl($artwork->image_url);
        }

        return response()->json($artworks);
    }

    public function show($id)
    {
        $artwork = Artwork::findOrFail($id);
        $artwork->image_url = $this->resolveUrl($artwork->image_url);
        
        return response()->json($artwork);
    }

    public function store(StoreArtworkRequest $request)
    {
        $data = $request->validated();
        
        $disk = config('filesystems.default', 'local');
        $uploadApi = null;

        if ($disk === 'cloudinary') {
            $this->initCloudinary();
            $uploadApi = new \Cloudinary\Api\Upload\UploadApi();
        }

        $files = [];
        if ($request->hasFile('images')) {
            $files = $request->file('images');
        } elseif ($request->hasFile('image')) {
            $files = [$request->file('image')];
        }

        $createdArtworks = [];

        foreach ($files as $file) {
            $imageUrl = $this->handleFileUpload(
                $file,
                'artworks',
                null,
                $disk,
                $uploadApi
            );

            $createdArtworks[] = Artwork::create([
                'image_url' => $imageUrl
            ]);
        }

        return response()->json([
            'message' => count($createdArtworks) > 1 ? 'Artworks created successfully' : 'Artwork created successfully',
            'data' => count($createdArtworks) > 1 ? $createdArtworks : $createdArtworks[0]
        ], 201);
    }

    public function update(UpdateArtworkRequest $request, $id)
    {
        $artwork = Artwork::findOrFail($id);
        $data = $request->validated();

        $disk = config('filesystems.default', 'local');
        $uploadApi = null;

        if ($disk === 'cloudinary') {
            $this->initCloudinary();
            $uploadApi = new \Cloudinary\Api\Upload\UploadApi();
        }

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->handleFileUpload(
                $request->file('image'),
                'artworks',
                $artwork->image_url,
                $disk,
                $uploadApi
            );
        }

        $artwork->update($data);

        return response()->json([
            'message' => 'Artwork updated successfully',
            'data' => $artwork
        ]);
    }

    public function destroy($id)
    {
        $artwork = Artwork::findOrFail($id);
        
        $disk = config('filesystems.default', 'local');
        
        // Delete image
        if ($artwork->image_url) {
            if ($disk === 'cloudinary' && !str_starts_with($artwork->image_url, 'http')) {
                 // Cloudinary handles cleanup or we leave it. Or implement destroy via Cloudinary api.
                 // We will skip cloudinary delete for simplicity just like in profile
            } else if (!str_starts_with($artwork->image_url, 'http')) {
                 if (Storage::disk($disk)->exists($artwork->image_url)) {
                     Storage::disk($disk)->delete($artwork->image_url);
                 }
            }
        }

        $artwork->delete();

        return response()->json([
            'message' => 'Artwork deleted successfully'
        ]);
    }

    private function handleFileUpload($file, $folder, $oldPath, $disk, $uploadApi)
    {
        try {
            if ($disk === 'cloudinary') {
                $result = $uploadApi->upload($file->getRealPath(), [
                    'folder' => $folder,
                    'resource_type' => 'image',
                    'access_mode' => 'public',
                    'overwrite' => true,
                ]);
                
                // Inject f_auto,q_auto into the Cloudinary URL to serve WebP/AVIF automatically
                $optimizedUrl = str_replace('/upload/', '/upload/f_auto,q_auto/', $result['secure_url']);
                return $optimizedUrl;
            } else {
                if ($oldPath && !str_starts_with($oldPath, 'http')) {
                    if (Storage::disk($disk)->exists($oldPath)) {
                        Storage::disk($disk)->delete($oldPath);
                    }
                }
                return $file->store($folder, $disk);
            }
        } catch (\Exception $e) {
            Log::error("Upload Error ({$folder}): " . $e->getMessage());
            throw $e;
        }
    }

    private function resolveUrl($path)
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return Storage::url($path);
    }
}
