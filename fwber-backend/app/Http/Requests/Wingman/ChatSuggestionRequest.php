<?php

namespace App\Http\Requests\Wingman;

use Illuminate\Foundation\Http\FormRequest;

class ChatSuggestionRequest extends FormRequest
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
            'match_id' => 'required|uuid|exists:matches,id',
            'tone' => 'nullable|string|in:funny,flirty,direct,casual,deep',
        ];
    }
}
