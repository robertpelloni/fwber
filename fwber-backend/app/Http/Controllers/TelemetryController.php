<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientEventsRequest;
use App\Services\TelemetryService;
use Illuminate\Http\JsonResponse;

class TelemetryController extends Controller
{
    public function storeClientEvents(StoreClientEventsRequest $request, TelemetryService $telemetry): JsonResponse
    {
        $validated = $request->validated();

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
