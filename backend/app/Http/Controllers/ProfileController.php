<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Contact;
use App\Models\Profile;
use App\Traits\ImageUploadTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    use ImageUploadTrait;

    public function update(UpdateProfileRequest $request)
    {
        // 1. Ambil data teks
        $data = $request->safe()->except(['photo_path', 'cv', 'secondary_image']);

        // 2. Ambil Profile atau Siapkan Instance Baru
        // PENTING: Gunakan firstOrNew.
        // Ini TIDAK menyimpan ke DB dulu, jadi aman dari error "Field 'name' doesn't have a default value"
        $profile = Profile::firstOrNew([], ['id' => 1]);

        // 3. (Removed config)

        // 4. Handle Uploads
        // (Logika ini tetap jalan normal karena $profile->photo_path tersedia jika data ada)

        // Handle Primary Photo
        if ($request->hasFile('photo_path')) {
            $data['photo_path'] = $this->handleFileUpload(
                $request->file('photo_path'),
                'profile',
                $profile->photo_path
            );
        }

        // Handle Secondary Image
        if ($request->hasFile('secondary_image')) {
            $data['secondary_image'] = $this->handleFileUpload(
                $request->file('secondary_image'),
                'profile-secondary',
                $profile->secondary_image
            );
        }

        // Handle CV
        if ($request->hasFile('cv')) {
            $data['cv_path'] = $this->handleFileUpload(
                $request->file('cv'),
                'cv',
                $profile->cv_path,
                'auto'
            );
        }

        // 5. Save & Refresh
        // DISINILAH penyimpanan ke Database terjadi.
        // Karena $data sudah berisi 'name', 'job_title' (dari request) DAN file paths,
        // maka Database akan menerimanya dengan sukses.
        $profile->fill($data);
        $profile->save();
        $profile->refresh();

        // 6. Append URLs
        $profile->photo_url = $this->resolveUrl($profile->photo_path);
        $profile->secondary_image_url = $this->resolveUrl($profile->secondary_image);
        $profile->cv_url = $this->resolveUrl($profile->cv_path);

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => $profile,
        ]);
    }

    // ... sisa method index, handleFileUpload, dan resolveUrl JANGAN DIUBAH (tetap pakai yang lama) ...

    public function index()
    {
        $profile = Profile::first();
        $contacts = Contact::whereRaw('LOWER(platform_name) != ?', ['email'])->get();

        if ($profile) {
            $profile->photo_url = $this->resolveUrl($profile->photo_path);
            $profile->secondary_image_url = $this->resolveUrl($profile->secondary_image);
            $profile->cv_url = $this->resolveUrl($profile->cv_path);
        }

        return response()->json([
            'about' => $profile,
            'social_media' => $contacts,
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
