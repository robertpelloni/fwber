<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="JoinProximityChatroomRequest",
 *   required={"latitude", "longitude"},
 *   @OA\Property(property="latitude", type="number", format="float", minimum=-90, maximum=90),
 *   @OA\Property(property="longitude", type="number", format="float", minimum=-180, maximum=180),
 *   @OA\Property(property="is_networking", type="boolean"),
 *   @OA\Property(property="is_social", type="boolean"),
 *   @OA\Property(property="professional_info", type="object"),
 *   @OA\Property(property="interests", type="array", @OA\Items(type="string"))
 * )
 */
class JoinProximityChatroomRequest extends FormRequest
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
            'is_networking' => 'boolean',
            'is_social' => 'boolean',
            'professional_info' => 'nullable|array',
            'interests' => 'nullable|array',
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
            'latitude.required' => 'Latitude is required.',
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.required' => 'Longitude is required.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
        ];
    }
}
