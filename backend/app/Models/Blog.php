<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'read_time',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($blog) {
            // Auto generate slug if title is modified
            if ($blog->isDirty('title')) {
                $blog->slug = Str::slug($blog->title);
                // Ensure slug uniqueness
                $originalSlug = $blog->slug;
                $count = 1;
                while (static::where('slug', $blog->slug)->where('id', '!=', $blog->id)->exists()) {
                    $blog->slug = $originalSlug . '-' . $count;
                    $count++;
                }
            }

            // Calculate read time
            if ($blog->isDirty('content')) {
                // Strip HTML tags to count pure words
                $plainText = strip_tags($blog->content);
                $wordCount = str_word_count($plainText);
                // Assume 200 words per minute, min 1 minute
                $minutes = (int) ceil($wordCount / 200);
                $blog->read_time = max(1, $minutes);
            }
        });
    }
}
