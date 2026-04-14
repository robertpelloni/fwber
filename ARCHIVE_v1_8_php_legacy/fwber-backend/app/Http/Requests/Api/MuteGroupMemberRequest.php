<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class MuteGroupMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'duration_minutes' => 'nullable|integer|min:1|max:10080', // up to 7 days
            'until' => 'nullable|date',
            'reason' => 'nullable|string|max:255',
        ];
    }
}
