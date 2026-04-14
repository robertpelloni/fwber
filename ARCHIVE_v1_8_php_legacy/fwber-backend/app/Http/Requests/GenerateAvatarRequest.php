<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateAvatarRequest extends FormRequest
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
            'style' => 'nullable|string|max:100',
            'provider' => 'nullable|string|in:dalle,gemini,replicate',
            'model' => 'nullable|string',
            'lora_scale' => 'nullable|numeric|min:0|max:1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'style.max' => 'Avatar style cannot exceed 100 characters.',
            'provider.in' => 'Provider must be one of: dalle, gemini, replicate.',
        ];
    }
}
