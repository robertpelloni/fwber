<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatroomRequest extends FormRequest
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
            'type' => 'required|in:interest,city,event,private',
            'category' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'neighborhood' => 'nullable|string|max:100',
            'is_public' => 'boolean',
            'settings' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Chatroom name is required.',
            'name.max' => 'Chatroom name cannot exceed 100 characters.',
            'description.max' => 'Description cannot exceed 500 characters.',
            'type.required' => 'Chatroom type is required.',
            'type.in' => 'Type must be one of: interest, city, event, private.',
            'category.max' => 'Category cannot exceed 50 characters.',
            'city.max' => 'City cannot exceed 100 characters.',
            'neighborhood.max' => 'Neighborhood cannot exceed 100 characters.',
        ];
    }
}
