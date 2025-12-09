<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="StoreProximityArtifactRequest",
 *   required={"type", "content", "lat", "lng"},
 *   @OA\Property(property="type", type="string", enum={"chat", "board_post", "announce"}, description="Artifact type"),
 *   @OA\Property(property="content", type="string", maxLength=500, description="Artifact content"),
 *   @OA\Property(property="lat", type="number", format="float", minimum=-90, maximum=90, description="Latitude"),
 *   @OA\Property(property="lng", type="number", format="float", minimum=-180, maximum=180, description="Longitude"),
 *   @OA\Property(property="radius", type="integer", minimum=100, maximum=10000, description="Visibility radius in meters")
 * )
 */
class StoreProximityArtifactRequest extends FormRequest
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
            'type' => 'required|in:chat,board_post,announce',
            'content' => 'required|string|max:500',
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
            'type.required' => 'Artifact type is required.',
            'type.in' => 'Artifact type must be chat, board_post, or announce.',
            'content.required' => 'Content is required.',
            'content.max' => 'Content must not exceed 500 characters.',
            'lat.required' => 'Latitude is required.',
            'lat.between' => 'Latitude must be between -90 and 90.',
            'lng.required' => 'Longitude is required.',
            'lng.between' => 'Longitude must be between -180 and 180.',
            'radius.min' => 'Radius must be at least 100 meters.',
            'radius.max' => 'Radius must not exceed 10000 meters.',
        ];
    }
}
