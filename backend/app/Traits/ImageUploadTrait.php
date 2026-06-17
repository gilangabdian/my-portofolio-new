<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Cloudinary\Cloudinary;
use Illuminate\Support\Str;

trait ImageUploadTrait
{
    /**
     * Upload an image to the appropriate disk (Cloudinary with WebP or Local)
     *
     * @param UploadedFile $file The image file to upload
     * @param string $folder The folder name (e.g., 'projects', 'artworks')
     * @param string|null $oldPath Optional old file path/URL to delete
     * @param string $resourceType Type of resource ('image', 'video', etc)
     * @return string The URL or path to the uploaded image
     */
    protected function handleFileUpload(UploadedFile $file, string $folder, ?string $oldPath = null, string $resourceType = 'image'): string
    {
        $disk = config('filesystems.default', 'public');

        // Delete old file if it exists
        if ($oldPath) {
            $this->deleteFile($oldPath, $disk);
        }

        if ($disk === 'cloudinary') {
            // Upload to Cloudinary using their API for better control over transformations
            $cloudinary = new Cloudinary(config('cloudinary.cloud_url'));
            
            $result = $cloudinary->uploadApi()->upload($file->getRealPath(), [
                'folder' => $folder,
                'resource_type' => $resourceType,
                'access_mode' => 'public',
                'overwrite' => true,
            ]);

            // Inject f_auto,q_auto into the Cloudinary URL to serve WebP/AVIF automatically
            return str_replace('/upload/', '/upload/f_auto,q_auto/', $result['secure_url']);
        } else {
            // Local fallback (development)
            return $file->store($folder, 'public');
        }
    }

    /**
     * Delete an image from the disk
     *
     * @param string|null $path The URL or path to delete
     * @param string|null $disk Force a specific disk, or null to auto-detect
     * @return void
     */
    protected function deleteFile(?string $path, ?string $disk = null): void
    {
        if (!$path) {
            return;
        }

        $disk = $disk ?? config('filesystems.default', 'public');

        if ($disk === 'cloudinary') {
            // Cloudinary deletion is complex because we need the public_id from the URL.
            // Often, users prefer not to delete from cloudinary to preserve CDN links,
            // or we use the cloudinary-laravel package's method. 
            // We will skip cloudinary programmatic deletion for safety/simplicity as previously done in Profile.
            // (Cloudinary handles overwrites if public_ids match, but we use random ids).
        } else {
            // Local disk deletion
            if (!str_starts_with($path, 'http')) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }
    }
}
