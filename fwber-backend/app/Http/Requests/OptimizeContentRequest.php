<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OptimizeContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|max:2000',
            'optimization_types' => 'sometimes|array',
            "optimization_types.*" => 'string|in:engagement,clarity,safety,relevance',
        ];
    }
}
