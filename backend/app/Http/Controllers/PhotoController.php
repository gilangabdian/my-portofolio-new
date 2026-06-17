<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePhotoRequest;
use App\Http\Requests\UpdatePhotoRequest;
use App\Models\Photo;
use Cloudinary\Configuration\Configuration;
use App\Traits\ImageUploadTrait;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    use ImageUploadTrait;

    public function index()
    {
        $photos = Photo::latest()->get();
        
        foreach ($photos as $photo) {
            $photo->image_url = $this->resolveUrl($photo->image_url);
        }

        return response()->json($photos);
    }

    public function show($id)
    {
        $photo = Photo::findOrFail($id);
        $photo->image_url = $this->resolveUrl($photo->image_url);
        
        return response()->json($photo);
    }

    public function store(StorePhotoRequest $request)
    {
        $data = $request->validated();
        


        $files = [];
        if ($request->hasFile('images')) {
            $files = $request->file('images');
        } elseif ($request->hasFile('image')) {
            $files = [$request->file('image')];
        }

        $createdPhotos = [];

        foreach ($files as $file) {
            $imageUrl = $this->handleFileUpload(
                $file,
                'photos'
            );

            $createdPhotos[] = Photo::create([
                'image_url' => $imageUrl
            ]);
        }

        return response()->json([
            'message' => count($createdPhotos) > 1 ? 'Photos created successfully' : 'Photo created successfully',
            'data' => count($createdPhotos) > 1 ? $createdPhotos : $createdPhotos[0]
        ], 201);
    }

    public function update(UpdatePhotoRequest $request, $id)
    {
        $photo = Photo::findOrFail($id);
        $data = $request->validated();



        if ($request->hasFile('image')) {
            $data['image_url'] = $this->handleFileUpload(
                $request->file('image'),
                'photos',
                $photo->image_url
            );
        }

        $photo->update($data);

        return response()->json([
            'message' => 'Photo updated successfully',
            'data' => $photo
        ]);
    }

    public function destroy($id)
    {
        $photo = Photo::findOrFail($id);
        
        $this->deleteFile($photo->image_url);

        $photo->delete();

        return response()->json([
            'message' => 'Photo deleted successfully'
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
