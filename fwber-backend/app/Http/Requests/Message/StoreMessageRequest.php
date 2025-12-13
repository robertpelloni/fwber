<?php

namespace App\Http\Requests\Message;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
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
            'content' => 'nullable|string|max:5000',
            'message_type' => 'sometimes|string|in:text,image,video,audio,file',
            'media' => 'nullable|file',
            // media_duration provided by clients for audio/video; we clamp to type-specific caps later
            'media_duration' => 'nullable|integer|min:1',
            'is_encrypted' => 'nullable|boolean',
        ];
    }
}
