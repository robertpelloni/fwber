<?php

namespace Tests\Feature;

use App\Models\TelemetryEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TelemetryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_events_are_persisted_with_user_context(): void
    {
        Config::set('telemetry.enabled', true);
        Config::set('telemetry.store_events', true);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/telemetry/client-events', [
            'events' => [
                [
                    'name' => 'face_blur_preview_ready',
                    'payload' => [
                        'preview_id' => 'preview-123',
                        'file_name' => 'photo.jpg',
                        'faces_detected' => 2,
                        'blur_applied' => true,
                        'processing_ms' => 110,
                        'backend' => 'client',
                    ],
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'processed' => 1,
                'failed' => 0,
            ]);

        $this->assertDatabaseHas('telemetry_events', [
            'event' => 'face_blur_preview_ready',
            'user_id' => $user->id,
        ]);

        $stored = TelemetryEvent::first();
        $this->assertNotNull($stored);
        $this->assertEquals('preview-123', $stored->payload['preview_id'] ?? null);
        $this->assertEquals('photo.jpg', $stored->payload['file_name'] ?? null);
        $this->assertNotNull($stored->recorded_at);
    }

    public function test_persistence_can_be_disabled_via_config(): void
    {
        Config::set('telemetry.enabled', true);
        Config::set('telemetry.store_events', false);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/telemetry/client-events', [
            'events' => [
                [
                    'name' => 'face_blur_preview_discarded',
                    'payload' => [
                        'preview_id' => 'preview-999',
                        'discard_reason' => 'user_removed',
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('telemetry_events', 0);
    }
}
