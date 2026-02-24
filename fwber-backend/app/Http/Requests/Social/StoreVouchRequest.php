<?php

namespace App\Http\Requests\Social;

use Illuminate\Foundation\Http\FormRequest;

class StoreVouchRequest extends FormRequest
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
            'referral_code' => 'required|string|exists:users,referral_code',
            'type' => 'required|string|in:safe,fun,hot',
            'relationship_type' => 'nullable|string|max:50',
            'comment' => 'nullable|string|max:500',
            'voucher_name' => 'nullable|string|max:100',
        ];
    }
}
