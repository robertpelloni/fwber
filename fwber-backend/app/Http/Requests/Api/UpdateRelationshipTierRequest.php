<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRelationshipTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'increment_messages' => 'sometimes|boolean',
            'mark_met_in_person' => 'sometimes|boolean',
        ];
    }
}
