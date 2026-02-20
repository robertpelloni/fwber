<?php

namespace App\Http\Requests\Merchant;

use Illuminate\Foundation\Http\FormRequest;

class StorePromotionRequest extends FormRequest
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
            'description' => 'required|string',
            'promo_code' => 'nullable|string|max:50',
            'discount_value' => 'required|string|max:50',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'required|integer|min:10|max:5000',
            'token_cost' => 'integer|min:0',
            'starts_at' => 'required|date',
            'expires_at' => 'required|date|after:starts_at',
        ];
    }
}
