<?php

namespace App\Http\Requests\Merchant;

use Illuminate\Foundation\Http\FormRequest;

class RegisterMerchantRequest extends FormRequest
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
            'business_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:50',
            'address' => 'nullable|string|max:255',
        ];
    }
}
