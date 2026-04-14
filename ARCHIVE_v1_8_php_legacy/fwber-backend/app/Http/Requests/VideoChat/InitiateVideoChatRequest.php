<?php

namespace App\Http\Requests\VideoChat;

use Illuminate\Foundation\Http\FormRequest;

class InitiateVideoChatRequest extends FormRequest
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
            'recipient_id' => 'required|integer|exists:users,id',
        ];
    }
}
