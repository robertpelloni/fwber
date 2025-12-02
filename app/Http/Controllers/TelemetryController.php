<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientEventsRequest;
use App\Services\TelemetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelemetryController extends Controller
{
    private const ALLOWED_CLIENT_EVENTS = [
        'face_blur_preview_ready',
        'face_blur_preview_toggled',
        'face_blur_preview_discarded',
    ];

    public function storeClientEvents(StoreClientEventsRequest $request, TelemetryService $telemetry): JsonResponse
    {
        // Additional validation for event names
        $validated = $request->validate([
            'events.*.name' => 'required|string|in:' . implode(',', self::ALLOWED_CLIENT_EVENTS),
            'events.*.payload' => 'required|array',
            'events.*.ts' => 'nullable|date',
        ]);

        $user = $request->user();
        $userId = $user ? (int)$user->id : null;

        $processed = 0;
        $failed = 0;

        foreach ($validated['events'] as $event) {
            $payload = $event['payload'];
            $payload['user_id'] = $userId;

            $ok = $telemetry->emit($event['name'], $payload);
            if ($ok) {
                $processed++;
            } else {
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'processed' => $processed,
            'failed' => $failed,
        ]);
    }
}
