<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'profile' => ['sometimes', 'array'],
            'profile.display_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profile.date_of_birth' => ['sometimes', 'nullable', 'date'],
            'profile.gender' => ['sometimes', 'nullable', 'string', 'max:100'],
            'profile.pronouns' => ['sometimes', 'nullable', 'string', 'max:100'],
            'profile.sexual_orientation' => ['sometimes', 'nullable', 'string', 'max:150'],
            'profile.relationship_style' => ['sometimes', 'nullable', 'string', 'max:150'],
            'profile.bio' => ['sometimes', 'nullable', 'string'],
            'profile.location_latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'profile.location_longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'profile.location_description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profile.sti_status' => ['sometimes', 'nullable', 'array'],
            'profile.preferences' => ['sometimes', 'nullable', 'array'],
            'profile.avatar_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
        ];
    }
}
