<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArtworkRequest;
use App\Http\Requests\UpdateArtworkRequest;
use App\Models\Artwork;
use App\Traits\ImageUploadTrait;
use Illuminate\Support\Facades\Storage;

class ArtworkController extends Controller
{
    use ImageUploadTrait;

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
                'artworks'
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

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->handleFileUpload(
                $request->file('image'), 
                'artworks', 
                $artwork->image_url
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
        $this->deleteFile($artwork->image_url);

        $artwork->delete();

        return response()->json([
            'message' => 'Artwork deleted successfully'
        ]);
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
