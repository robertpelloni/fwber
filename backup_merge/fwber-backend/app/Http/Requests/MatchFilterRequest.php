<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MatchFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'age_min' => 'nullable|integer|min:18|max:100',
            'age_max' => 'nullable|integer|min:18|max:100',
            'max_distance' => 'nullable|integer|min:1|max:500',
            'smoking' => 'nullable|string|in:non-smoker,occasional,regular,social,trying-to-quit',
            'drinking' => 'nullable|string|in:non-drinker,occasional,regular,social,sober',
            'body_type' => 'nullable|string|in:slim,athletic,average,curvy,plus-size,muscular',
            'height_min' => 'nullable|integer|min:120|max:250',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'age_min.min' => 'Minimum age must be at least 18.',
            'age_min.max' => 'Minimum age cannot exceed 100.',
            'age_max.min' => 'Maximum age must be at least 18.',
            'age_max.max' => 'Maximum age cannot exceed 100.',
            'max_distance.min' => 'Distance must be at least 1 mile.',
            'max_distance.max' => 'Distance cannot exceed 500 miles.',
            'smoking.in' => 'Invalid smoking preference value.',
            'drinking.in' => 'Invalid drinking preference value.',
            'body_type.in' => 'Invalid body type value.',
            'height_min.min' => 'Minimum height must be at least 120cm.',
            'height_min.max' => 'Minimum height cannot exceed 250cm.',
        ];
    }
}
