<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by Sanctum middleware
    }

    public function rules(): array
    {
        return [
            'latitude' => 'required',
            'longitude' => 'required',
            'accuracy' => 'sometimes',
            'privacy_level' => 'sometimes|string|in:public,friends,private',
            'heading' => 'sometimes|numeric',
            'speed' => 'sometimes|numeric',
            'altitude' => 'sometimes|numeric',
        ];
    }
}
