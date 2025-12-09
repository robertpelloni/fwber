<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="StoreProximityChatroomMessageRequest",
 *   required={"content"},
 *   @OA\Property(property="content", type="string", maxLength=2000),
 *   @OA\Property(property="message_type", type="string", enum={"text", "image", "file", "announcement"}),
 *   @OA\Property(property="parent_id", type="integer", nullable=true),
 *   @OA\Property(property="is_networking", type="boolean"),
 *   @OA\Property(property="is_social", type="boolean"),
 *   @OA\Property(property="metadata", type="object")
 * )
 */
class StoreProximityChatroomMessageRequest extends FormRequest
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
            'content' => 'required|string|max:2000',
            'message_type' => 'nullable|in:text,image,file,announcement',
            'parent_id' => 'nullable|exists:proximity_chatroom_messages,id',
            'is_networking' => 'boolean',
            'is_social' => 'boolean',
            'metadata' => 'nullable|array',
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
            'content.required' => 'Message content is required.',
            'content.max' => 'Message must not exceed 2000 characters.',
            'message_type.in' => 'Invalid message type.',
            'parent_id.exists' => 'Parent message not found.',
        ];
    }
}
