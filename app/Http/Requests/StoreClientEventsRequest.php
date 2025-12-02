<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientEventsRequest extends FormRequest
{
    private const ALLOWED_CLIENT_EVENTS = [
        'face_blur_preview_ready',
        'face_blur_preview_toggled',
        'face_blur_preview_discarded',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'events' => 'required|array|min:1|max:50',
            'events.*.name' => 'required|string|in:' . implode(',', self::ALLOWED_CLIENT_EVENTS),
            'events.*.payload' => 'required|array',
            'events.*.ts' => 'nullable|date',
        ];
    }
}
