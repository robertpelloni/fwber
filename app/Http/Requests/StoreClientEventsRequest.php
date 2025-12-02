<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'events' => 'required|array|min:1|max:50',
            'events.*.event_type' => 'required|string|max:100',
            'events.*.timestamp' => 'required|date',
            'events.*.data' => 'sometimes|array',
        ];
    }
}
