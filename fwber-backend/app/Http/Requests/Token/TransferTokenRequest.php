<?php

namespace App\Http\Requests\Token;

use Illuminate\Foundation\Http\FormRequest;

class TransferTokenRequest extends FormRequest
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
            'amount' => 'required|numeric|min:1',
            'recipient_id' => 'required_without:recipient_email|exists:users,id',
            'recipient_email' => 'required_without:recipient_id|email|exists:users,email',
            'message' => 'nullable|string|max:255',
        ];
    }
}
