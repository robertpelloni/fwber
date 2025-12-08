<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VenueSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'integer|min:100|max:50000',
        ];
    }
}
