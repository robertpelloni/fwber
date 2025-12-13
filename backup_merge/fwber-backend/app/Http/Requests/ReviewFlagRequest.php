<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewFlagRequest extends FormRequest
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
            'action' => 'required|in:dismiss,remove,throttle_user,ban_user',
            'reason' => 'required|string|max:500',
            'throttle_severity' => 'nullable|integer|min:1|max:5',
            'throttle_duration_hours' => 'nullable|integer|min:1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Moderation action is required.',
            'action.in' => 'Action must be one of: dismiss, remove, throttle_user, ban_user.',
            'reason.required' => 'A reason for the action is required.',
            'reason.max' => 'Reason cannot exceed 500 characters.',
            'throttle_severity.min' => 'Throttle severity must be at least 1.',
            'throttle_severity.max' => 'Throttle severity cannot exceed 5.',
            'throttle_duration_hours.min' => 'Throttle duration must be at least 1 hour.',
        ];
    }
}
