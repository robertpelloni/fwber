<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatroomMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => 'required|string|max:2000',
            'type' => 'nullable|in:text,image,file,announcement',
            'parent_id' => 'nullable|exists:chatroom_messages,id',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Message content is required',
            'content.max' => 'Message cannot exceed 2000 characters',
            'type.in' => 'Message type must be: text, image, file, or announcement',
            'parent_id.exists' => 'Parent message not found',
        ];
    }
}
