<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Certificate extends Model
{
    const TYPES = ['course', 'seminar', 'webinar', 'workshop', 'bootcamp', 'competition'];

    protected $fillable = ['title', 'issuer', 'description', 'is_featured', 'start_date', 'end_date', 'type', 'image_path', 'credential_link'];

    protected $casts = [
        'is_featured' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Otomatis tambahkan image_url ke output JSON
    protected $appends = ['image_url'];

    protected function getImageUrlAttribute()
    {
        if (!$this->image_path) {
            return null;
        }

        // Jika path sudah berupa URL utuh (seperti dari Cloudinary), gunakan langsung
        if (str_starts_with($this->image_path, 'http')) {
            return $this->image_path;
        }

        // Jika bukan URL utuh, ambil dari local storage
        return Storage::disk('public')->url($this->image_path);
    }
}
