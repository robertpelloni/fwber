<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchChatroomRequest extends FormRequest
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
            'q' => 'required|string|min:2|max:100',
            'type' => 'nullable|in:interest,city,event,private',
            'category' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'q.required' => 'Search query is required.',
            'q.min' => 'Search query must be at least 2 characters.',
            'q.max' => 'Search query cannot exceed 100 characters.',
            'type.in' => 'Type must be one of: interest, city, event, private.',
        ];
    }
}
