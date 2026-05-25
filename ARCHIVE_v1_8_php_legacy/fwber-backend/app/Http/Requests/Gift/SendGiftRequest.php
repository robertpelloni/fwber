<?php

namespace App\Http\Requests\Gift;

use Illuminate\Foundation\Http\FormRequest;

class SendGiftRequest extends FormRequest
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
            'receiver_id' => 'required|exists:users,id',
            'gift_id' => 'required|exists:gifts,id',
            'message' => 'nullable|string|max:255',
        ];
    }
}
