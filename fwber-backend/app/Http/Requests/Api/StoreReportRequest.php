<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'accused_id' => 'required|integer|exists:users,id',
            'message_id' => 'nullable|integer|exists:messages,id',
            'reason' => 'required|string|max:100',
            'details' => 'nullable|string|max:2000',
        ];
    }
}
