<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewSpoofRequest extends FormRequest
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
            'action' => 'required|in:confirm,dismiss',
            'reason' => 'required|string|max:500',
            'apply_throttle' => 'nullable|boolean',
            'throttle_severity' => 'nullable|integer|min:1|max:5',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Review action is required.',
            'action.in' => 'Action must be either confirm or dismiss.',
            'reason.required' => 'A reason for the review is required.',
            'reason.max' => 'Reason cannot exceed 500 characters.',
            'apply_throttle.boolean' => 'Apply throttle must be true or false.',
            'throttle_severity.min' => 'Throttle severity must be at least 1.',
            'throttle_severity.max' => 'Throttle severity cannot exceed 5.',
        ];
    }
}
