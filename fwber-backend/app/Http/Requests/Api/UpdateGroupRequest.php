<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:1000',
            'visibility' => 'sometimes|in:public,private',
            'max_members' => 'sometimes|integer|min:2|max:500',
            'category' => 'nullable|string|max:50',
            'tags' => 'nullable|array',
            'matching_enabled' => 'boolean',
            'location_lat' => 'nullable|numeric',
            'location_lon' => 'nullable|numeric',
        ];
    }
}
