<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MatchActionRequest extends FormRequest
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
            'action' => 'required|in:like,pass,super_like',
            'target_user_id' => 'required|integer|exists:users,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Action is required.',
            'action.in' => 'Action must be one of: like, pass, super_like.',
            'target_user_id.required' => 'Target user ID is required.',
            'target_user_id.integer' => 'Target user ID must be an integer.',
            'target_user_id.exists' => 'The specified user does not exist.',
        ];
    }
}
