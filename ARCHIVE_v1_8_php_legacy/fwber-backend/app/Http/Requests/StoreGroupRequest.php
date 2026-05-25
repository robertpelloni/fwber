<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'icon' => 'nullable|string|max:100',
            'privacy' => 'required|in:public,private',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Group name is required.',
            'name.max' => 'Group name cannot exceed 255 characters.',
            'description.max' => 'Group description cannot exceed 2000 characters.',
            'icon.max' => 'Icon identifier cannot exceed 100 characters.',
            'privacy.required' => 'Privacy setting is required.',
            'privacy.in' => 'Privacy must be either public or private.',
        ];
    }
}
