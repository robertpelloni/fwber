<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TelemetryService;
use Mockery;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseSilently;

class TelemetryClientEventsTest extends TestCase
{
    use RefreshDatabaseSilently;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_authenticated_user_can_submit_preview_events(): void
    {
        $user = User::factory()->create();

        $telemetry = Mockery::mock(TelemetryService::class);
        $telemetry->shouldReceive('emit')
            ->once()
            ->with('face_blur_preview_ready', Mockery::on(function ($payload) use ($user) {
                return ($payload['user_id'] ?? null) === $user->id
                    && ($payload['preview_id'] ?? null) === 'preview-1'
                    && ($payload['blur_applied'] ?? null) === true;
            }))
            ->andReturn(true);
        $telemetry->shouldReceive('emit')
            ->once()
            ->with('face_blur_preview_discarded', Mockery::on(function ($payload) use ($user) {
                return ($payload['user_id'] ?? null) === $user->id
                    && ($payload['preview_id'] ?? null) === 'preview-1'
                    && ($payload['discard_reason'] ?? null) === 'user_removed';
            }))
            ->andReturn(true);

        $this->app->instance(TelemetryService::class, $telemetry);

        $response = $this->actingAs($user)->postJson('/api/telemetry/client-events', [
            'events' => [
                [
                    'name' => 'face_blur_preview_ready',
                    'payload' => [
                        'preview_id' => 'preview-1',
                        'file_name' => 'photo.jpg',
                        'faces_detected' => 2,
                        'blur_applied' => true,
                        'processing_ms' => 140,
                        'backend' => 'client',
                    ],
                ],
                [
                    'name' => 'face_blur_preview_discarded',
                    'payload' => [
                        'preview_id' => 'preview-1',
                        'discard_reason' => 'user_removed',
                    ],
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'processed' => 2,
                'failed' => 0,
            ]);
    }

    public function test_invalid_event_name_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/telemetry/client-events', [
            'events' => [
                [
                    'name' => 'unknown.event',
                    'payload' => [],
                ],
            ],
        ]);

        $response->assertStatus(422);
    }
}
