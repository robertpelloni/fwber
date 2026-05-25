<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVenueCheckinRequest extends FormRequest
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
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'message' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'latitude.required' => 'Latitude is required for check-in.',
            'latitude.numeric' => 'Latitude must be a valid number.',
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.required' => 'Longitude is required for check-in.',
            'longitude.numeric' => 'Longitude must be a valid number.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
            'message.max' => 'Check-in message cannot exceed 255 characters.',
        ];
    }
}
