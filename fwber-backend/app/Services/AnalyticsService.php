<?php

namespace App\Services;

use App\Models\ClickstreamEvent;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalyticsService
{
    /**
     * Record a new clickstream event.
     *
     * @return bool
     */
    public function recordEvent(
        string $sessionId,
        string $eventName,
        array $payload = [],
        ?int $userId = null,
        ?string $url = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        try {
            ClickstreamEvent::create([
                'session_id' => $sessionId,
                'event_name' => $eventName,
                'payload' => $payload,
                'user_id' => $userId,
                'url' => $url,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to record clickstream event', [
                'error' => $e->getMessage(),
                'event' => $eventName,
                'session' => $sessionId,
            ]);
            // Do not throw to avoid breaking the main requests
        }
    }
}
