<?php

namespace Tests\Feature;

use App\Models\ModerationAction;
use App\Models\ProximityArtifact;
use App\Models\Report;
use App\Models\ShadowThrottle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SafetyAndModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_report(): void
    {
        $report = Report::factory()->create();

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(User::class, $report->reporter);
        $this->assertInstanceOf(User::class, $report->accused);
    }

    public function test_can_create_moderation_action(): void
    {
        $action = ModerationAction::factory()->create();

        $this->assertDatabaseHas('moderation_actions', [
            'id' => $action->id,
            'action_type' => $action->action_type,
        ]);
    }

    public function test_can_create_shadow_throttle(): void
    {
        $user = User::factory()->create();
        
        $throttle = ShadowThrottle::create([
            'user_id' => $user->id,
            'reason' => 'spam',
            'severity' => 5,
            'visibility_reduction' => 0.8,
        ]);

        $this->assertDatabaseHas('shadow_throttles', [
            'id' => $throttle->id,
            'user_id' => $user->id,
            'reason' => 'spam',
        ]);
    }

    public function test_can_create_proximity_artifact(): void
    {
        $user = User::factory()->create();

        $artifact = ProximityArtifact::create([
            'user_id' => $user->id,
            'type' => 'note',
            'content' => 'Hello World',
            'location_lat' => 40.7128,
            'location_lng' => -74.0060,
            'visibility_radius_m' => 50,
        ]);

        $this->assertDatabaseHas('proximity_artifacts', [
            'id' => $artifact->id,
            'content' => 'Hello World',
        ]);
    }
}
