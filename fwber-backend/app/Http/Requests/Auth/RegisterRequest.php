<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $profile = $this->input("profile");

        if (is_array($profile)) {
            $this->merge([
                "profile" => collect($profile)
                    ->mapWithKeys(fn ($value, $key) => [Str::snake($key) => $value])
                    ->toArray(),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            "name" => ["required", "string", "max:255"],
            "email" => ["required", "string", "email", "max:255", "unique:users,email"],
            "password" => ["required", "string", "min:8", "confirmed"],
            "profile" => ["nullable", "array"],
            "profile.display_name" => ["nullable", "string", "max:255"],
            "profile.date_of_birth" => ["nullable", "date"],
            "profile.gender" => ["nullable", "string", "max:100"],
            "profile.pronouns" => ["nullable", "string", "max:100"],
            "profile.sexual_orientation" => ["nullable", "string", "max:150"],
            "profile.relationship_style" => ["nullable", "string", "max:150"],
            "profile.bio" => ["nullable", "string"],
            "profile.location_latitude" => ["nullable", "numeric", "between:-90,90"],
            "profile.location_longitude" => ["nullable", "numeric", "between:-180,180"],
            "profile.location_description" => ["nullable", "string", "max:255"],
            "profile.sti_status" => ["nullable", "array"],
            "profile.preferences" => ["nullable", "array"],
            "profile.avatar_url" => ["nullable", "string", "max:2048"],
        ];
    }
}
