<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreArtworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp,avif|max:10240',
            'image' => 'required_without:images|image|mimes:jpeg,png,jpg,webp,avif|max:10240',
        ];
    }
}
