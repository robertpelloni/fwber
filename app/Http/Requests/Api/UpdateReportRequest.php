<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|string|in:open,reviewing,resolved,dismissed',
            'resolution_notes' => 'nullable|string|max:5000',
        ];
    }
}
