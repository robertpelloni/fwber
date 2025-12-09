<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProximityFeedRequest extends FormRequest
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
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:100|max:10000',
            'type' => 'nullable|in:chat,board_post,announce',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'lat.required' => 'Latitude is required.',
            'lat.between' => 'Latitude must be between -90 and 90.',
            'lng.required' => 'Longitude is required.',
            'lng.between' => 'Longitude must be between -180 and 180.',
            'radius.min' => 'Radius must be at least 100 meters.',
            'radius.max' => 'Radius cannot exceed 10,000 meters.',
            'type.in' => 'Type must be one of: chat, board_post, announce.',
        ];
    }
}
