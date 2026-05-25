<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'type' => 'nullable|string|in:standard,speed_dating,party,meetup,workshop',
            'location_name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'starts_at' => 'required|date|after:now',
            'ends_at' => 'required|date|after:starts_at',
            'max_attendees' => 'nullable|integer|min:1|max:10000',
            'price' => 'nullable|numeric|min:0|max:9999.99',
            'token_cost' => 'nullable|numeric|min:0',
            'shared_group_ids' => 'nullable|array',
            'shared_group_ids.*' => 'integer|exists:groups,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Event title is required.',
            'title.max' => 'Event title cannot exceed 255 characters.',
            'description.required' => 'Event description is required.',
            'description.max' => 'Event description cannot exceed 5000 characters.',
            'location_name.required' => 'Location name is required.',
            'latitude.required' => 'Latitude is required.',
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.required' => 'Longitude is required.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
            'starts_at.required' => 'Start date/time is required.',
            'starts_at.after' => 'Event must start in the future.',
            'ends_at.required' => 'End date/time is required.',
            'ends_at.after' => 'End time must be after start time.',
            'max_attendees.min' => 'Maximum attendees must be at least 1.',
            'max_attendees.max' => 'Maximum attendees cannot exceed 10,000.',
            'price.min' => 'Price cannot be negative.',
        ];
    }
}
