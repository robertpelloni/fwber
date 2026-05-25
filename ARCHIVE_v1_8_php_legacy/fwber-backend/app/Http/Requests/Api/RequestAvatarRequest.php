<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RequestAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'style' => 'required|string|in:realistic,anime,fantasy,sci-fi,cartoon,pixel-art',
        ];
    }
}
