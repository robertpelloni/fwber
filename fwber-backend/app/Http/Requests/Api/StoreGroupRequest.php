<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'visibility' => 'sometimes|in:public,private',
            'max_members' => 'sometimes|integer|min:2|max:500',
            'token_entry_fee' => 'sometimes|numeric|min:0',
        ];
    }
}
