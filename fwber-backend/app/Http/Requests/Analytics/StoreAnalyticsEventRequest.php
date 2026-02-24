<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnalyticsEventRequest extends FormRequest
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
            'session_id' => 'required|string|max:255',
            'events' => 'required|array',
            'events.*.event_name' => 'required|string|max:255',
            'events.*.payload' => 'nullable|array',
            'events.*.url' => 'nullable|string|max:1000',
        ];
    }
}
