<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpsertPhysicalProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'height_cm' => 'nullable|integer|min:80|max:250',
            'body_type' => 'nullable|string|max:50',
            'hair_color' => 'nullable|string|max:50',
            'eye_color' => 'nullable|string|max:50',
            'skin_tone' => 'nullable|string|max:50',
            'ethnicity' => 'nullable|string|max:50',
            'facial_hair' => 'nullable|string|max:50',
            'tattoos' => 'nullable|boolean',
            'piercings' => 'nullable|boolean',
            'dominant_hand' => 'nullable|string|in:left,right,ambi',
            'fitness_level' => 'nullable|string|in:low,average,fit,athletic',
            'clothing_style' => 'nullable|string|max:50',
            'avatar_prompt' => 'nullable|string|max:500',
        ];
    }
}
