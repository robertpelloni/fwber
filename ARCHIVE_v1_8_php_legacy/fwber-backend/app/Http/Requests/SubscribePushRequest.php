<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribePushRequest extends FormRequest
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
            'endpoint' => 'required|string|url',
            'keys.auth' => 'required|string',
            'keys.p256dh' => 'required|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'endpoint.required' => 'Push endpoint URL is required.',
            'endpoint.url' => 'Push endpoint must be a valid URL.',
            'keys.auth.required' => 'Auth key is required for push subscription.',
            'keys.p256dh.required' => 'P256DH key is required for push subscription.',
        ];
    }
}
