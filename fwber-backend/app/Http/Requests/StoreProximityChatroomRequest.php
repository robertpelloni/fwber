<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="StoreProximityChatroomRequest",
 *   required={"name", "type", "latitude", "longitude"},
 *   @OA\Property(property="name", type="string", maxLength=100),
 *   @OA\Property(property="description", type="string", maxLength=500),
 *   @OA\Property(property="type", type="string", enum={"conference", "event", "venue", "area", "temporary"}),
 *   @OA\Property(property="venue_name", type="string", maxLength=100),
 *   @OA\Property(property="venue_type", type="string", maxLength=50),
 *   @OA\Property(property="event_name", type="string", maxLength=100),
 *   @OA\Property(property="event_date", type="string", format="date"),
 *   @OA\Property(property="event_start_time", type="string", format="time"),
 *   @OA\Property(property="event_end_time", type="string", format="time"),
 *   @OA\Property(property="latitude", type="number", format="float", minimum=-90, maximum=90),
 *   @OA\Property(property="longitude", type="number", format="float", minimum=-180, maximum=180),
 *   @OA\Property(property="radius_meters", type="integer", minimum=50, maximum=1000),
 *   @OA\Property(property="city", type="string", maxLength=100),
 *   @OA\Property(property="neighborhood", type="string", maxLength=100),
 *   @OA\Property(property="address", type="string", maxLength=200),
 *   @OA\Property(property="tags", type="array", @OA\Items(type="string")),
 *   @OA\Property(property="max_members", type="integer", minimum=2, maximum=1000),
 *   @OA\Property(property="requires_approval", type="boolean"),
 *   @OA\Property(property="expires_at", type="string", format="date-time")
 * )
 */
class StoreProximityChatroomRequest extends FormRequest
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
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:conference,event,venue,area,temporary',
            'venue_name' => 'nullable|string|max:100',
            'venue_type' => 'nullable|string|max:50',
            'event_name' => 'nullable|string|max:100',
            'event_date' => 'nullable|date',
            'event_start_time' => 'nullable|date_format:H:i',
            'event_end_time' => 'nullable|date_format:H:i',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meters' => 'nullable|integer|min:50|max:1000',
            'city' => 'nullable|string|max:100',
            'neighborhood' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:200',
            'tags' => 'nullable|array',
            'max_members' => 'nullable|integer|min:2|max:1000',
            'requires_approval' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
            'token_entry_fee' => 'sometimes|numeric|min:0',
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
            'name.required' => 'Chatroom name is required.',
            'name.max' => 'Chatroom name must not exceed 100 characters.',
            'type.required' => 'Chatroom type is required.',
            'type.in' => 'Invalid chatroom type.',
            'latitude.required' => 'Latitude is required.',
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.required' => 'Longitude is required.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
            'radius_meters.min' => 'Radius must be at least 50 meters.',
            'radius_meters.max' => 'Radius must not exceed 1000 meters.',
            'max_members.min' => 'Maximum members must be at least 2.',
            'max_members.max' => 'Maximum members must not exceed 1000.',
            'expires_at.after' => 'Expiration date must be in the future.',
        ];
    }
}
