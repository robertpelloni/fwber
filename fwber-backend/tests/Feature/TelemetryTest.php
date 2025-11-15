<?php

namespace Tests\Feature;

use App\Services\TelemetryService;
use Tests\TestCase;

class TelemetryTest extends TestCase
{
    public function test_emits_valid_event_and_aggregates(): void
    {
        $telemetry = app(TelemetryService::class);

        $ok = $telemetry->emit('user.signup', [
            'user_id' => 1,
            'method' => 'email',
        ]);
        $this->assertTrue($ok);

        $counts = $telemetry->getCounts();
        $this->assertArrayHasKey('user.signup', $counts);
        $this->assertGreaterThanOrEqual(1, $counts['user.signup']);
    }

    public function test_validation_failure_returns_false(): void
    {
        $telemetry = app(TelemetryService::class);

        $ok = $telemetry->emit('message.sent', [
            // Missing required keys
            'from_user_id' => 1,
        ]);
        $this->assertFalse($ok);
    }
}
