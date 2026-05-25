<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'nullable|string|max:5000',
            'message_type' => 'sometimes|string|in:text,image,video,audio,file',
            'media' => 'nullable|file',
            'media_duration' => 'nullable|integer|min:1',
        ];
    }
}
