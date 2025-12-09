<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="MessageReactionRequest",
 *   required={"emoji"},
 *   @OA\Property(property="emoji", type="string", maxLength=10, description="Emoji character for reaction")
 * )
 */
class MessageReactionRequest extends FormRequest
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
            'emoji' => 'required|string|max:10',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'emoji.required' => 'Emoji is required.',
            'emoji.max' => 'Emoji must not exceed 10 characters.',
        ];
    }
}
