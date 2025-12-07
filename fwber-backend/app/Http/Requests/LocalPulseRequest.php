<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="LocalPulseRequest",
 *   required={"lat", "lng"},
 *   @OA\Property(property="lat", type="number", format="float", minimum=-90, maximum=90, description="Latitude"),
 *   @OA\Property(property="lng", type="number", format="float", minimum=-180, maximum=180, description="Longitude"),
 *   @OA\Property(property="radius", type="integer", minimum=100, maximum=10000, description="Search radius in meters")
 * )
 */
class LocalPulseRequest extends FormRequest
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
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lat.required' => 'Latitude is required.',
            'lat.between' => 'Latitude must be between -90 and 90.',
            'lng.required' => 'Longitude is required.',
            'lng.between' => 'Longitude must be between -180 and 180.',
            'radius.min' => 'Radius must be at least 100 meters.',
            'radius.max' => 'Radius must not exceed 10000 meters.',
        ];
    }
}
