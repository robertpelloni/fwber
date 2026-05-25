<?php

namespace App\Http\Requests\Token;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawTokenRequest extends FormRequest
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
            'amount' => 'required|numeric|min:0.1',
            'destination_address' => ['required', 'string', 'regex:/^[1-9A-HJ-NP-Za-km-z]{32,44}$/'],
        ];
    }
}
