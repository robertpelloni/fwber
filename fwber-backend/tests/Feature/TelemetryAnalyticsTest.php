<?php

namespace Tests\Feature;

use App\Models\TelemetryEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TelemetryAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_preview_summary_requires_feature_flag_and_moderator(): void
    {
        config(['features.analytics' => false]);

        $user = User::factory()->create(['is_moderator' => false]);

        $this->actingAs($user)
            ->getJson('/api/telemetry/preview-summary')
            ->assertStatus(404);

        config(['features.analytics' => true]);

        $this->actingAs($user)
            ->getJson('/api/telemetry/preview-summary')
            ->assertStatus(403);

        $moderator = User::factory()->create(['is_moderator' => true]);

        $this->actingAs($moderator)
            ->getJson('/api/telemetry/preview-summary')
            ->assertOk()
            ->assertJsonStructure([
                'preview_ready' => ['total'],
                'toggles' => ['events'],
                'window' => ['range'],
            ]);
    }

    public function test_preview_summary_returns_aggregated_metrics(): void
    {
        config(['features.analytics' => true]);

        $moderator = User::factory()->create(['is_moderator' => true]);
        $now = now();

        TelemetryEvent::create([
            'user_id' => $moderator->id,
            'event' => 'face_blur_preview_ready',
            'payload' => [
                'user_id' => $moderator->id,
                'preview_id' => 'prev-1',
                'file_name' => 'photo-a.jpg',
                'faces_detected' => 2,
                'blur_applied' => true,
                'processing_ms' => 120,
                'backend' => 'client',
            ],
            'recorded_at' => $now->copy()->subHours(2),
        ]);

        TelemetryEvent::create([
            'user_id' => $moderator->id,
            'event' => 'face_blur_preview_ready',
            'payload' => [
                'user_id' => $moderator->id,
                'preview_id' => 'prev-2',
                'file_name' => 'photo-b.jpg',
                'faces_detected' => 0,
                'blur_applied' => false,
                'processing_ms' => 200,
                'backend' => 'client',
                'warning' => 'no_faces_detected',
            ],
            'recorded_at' => $now->copy()->subHours(3),
        ]);

        TelemetryEvent::create([
            'user_id' => $moderator->id,
            'event' => 'face_blur_preview_toggled',
            'payload' => [
                'user_id' => $moderator->id,
                'preview_id' => 'prev-1',
                'view' => 'original',
                'faces_detected' => 2,
                'blur_applied' => true,
            ],
            'recorded_at' => $now->copy()->subHours(2),
        ]);

        TelemetryEvent::create([
            'user_id' => $moderator->id,
            'event' => 'face_blur_preview_toggled',
            'payload' => [
                'user_id' => $moderator->id,
                'preview_id' => 'prev-2',
                'view' => 'processed',
                'faces_detected' => 0,
                'blur_applied' => false,
            ],
            'recorded_at' => $now->copy()->subHours(1),
        ]);

        TelemetryEvent::create([
            'user_id' => $moderator->id,
            'event' => 'face_blur_preview_discarded',
            'payload' => [
                'user_id' => $moderator->id,
                'preview_id' => 'prev-2',
                'faces_detected' => 0,
                'blur_applied' => false,
                'discard_reason' => 'user_removed',
            ],
            'recorded_at' => $now->copy()->subHour(),
        ]);

        TelemetryEvent::create([
            'user_id' => $moderator->id,
            'event' => 'face_blur_applied',
            'payload' => [
                'user_id' => $moderator->id,
                'photo_filename' => 'photo-a-blurred.jpg',
                'original_filename' => 'photo-a.jpg',
                'faces_detected' => 2,
                'processing_ms' => 130,
                'client_backend' => 'client',
                'preview_id' => 'prev-1',
            ],
            'recorded_at' => $now->copy()->subHour(),
        ]);

        TelemetryEvent::create([
            'user_id' => $moderator->id,
            'event' => 'face_blur_skipped_reason',
            'payload' => [
                'user_id' => $moderator->id,
                'photo_filename' => 'photo-b.jpg',
                'original_filename' => 'photo-b.jpg',
                'reason' => 'no_faces_detected',
                'faces_detected' => 0,
                'client_backend' => 'client',
                'preview_id' => 'prev-2',
            ],
            'recorded_at' => $now->copy()->subMinutes(30),
        ]);

        $this->actingAs($moderator)
            ->getJson('/api/telemetry/preview-summary?range=7d')
            ->assertOk()
            ->assertJsonPath('preview_ready.total', 2)
            ->assertJsonPath('preview_ready.blur_applied.count', 1)
            ->assertJsonPath('toggles.unique_previews', 2)
            ->assertJsonPath('discarded.events', 1)
            ->assertJsonPath('discarded.reasons.0.value', 'user_removed')
            ->assertJsonPath('uploads.applied', 1)
            ->assertJsonPath('uploads.skip_reasons.0.value', 'no_faces_detected')
            ->assertJsonPath('window.range', '7d');
    }
}
