<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ApiToken;
use App\Models\ProximityArtifact;
use App\Models\ShadowThrottle;
use App\Models\GeoSpoofDetection;
use App\Models\ModerationAction;
use App\Services\ShadowThrottleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationControllerTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as baseRefreshDatabase;
    }

    private User $moderator;
    private User $regularUser;
    private string $moderatorToken;

    /**
     * Override refreshDatabase to run migrations without confirmation.
     */
    protected function refreshDatabase(): void
    {
        // Run migrations programmatically without console confirmation
        $this->artisan('migrate:fresh', ['--force' => true])->run();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create moderator and regular user
        $this->moderator = User::factory()->create(['is_moderator' => true]);
        $this->regularUser = User::factory()->create(['is_moderator' => false]);

        // Generate auth token for moderator using custom ApiToken
        $this->moderatorToken = ApiToken::generateForUser($this->moderator, 'test');

        // Enable required feature flags for these tests
        config([
            'features.moderation' => true,
            // Authenticated API routes are wrapped with rate limit feature middleware
            'features.rate_limits' => true,
        ]);
    }

    public function test_moderation_routes_return_404_when_feature_disabled(): void
    {
        config(['features.moderation' => false]);

        $response = $this->withToken($this->moderatorToken)
            ->getJson('/api/moderation/dashboard');

        $response->assertNotFound();
    }

    public function test_non_moderators_cannot_access_moderation_dashboard(): void
    {
        $regularToken = ApiToken::generateForUser($this->regularUser, 'test');

        $response = $this->withToken($regularToken)
            ->getJson('/api/moderation/dashboard');

        $response->assertForbidden();
    }

    public function test_moderator_can_access_dashboard(): void
    {
        // Create some test data
        ProximityArtifact::factory()->create(['is_flagged' => true]);
        ShadowThrottle::create([
            'user_id' => $this->regularUser->id,
            'reason' => 'Test',
            'severity' => 2,
            'visibility_reduction' => 0.50,
            'started_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->withToken($this->moderatorToken)
            ->getJson('/api/moderation/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'stats' => [
                    'flagged_artifacts',
                    'active_throttles',
                    'pending_spoof_detections',
                    'moderation_actions_today',
                ],
                'recent_actions',
            ]);

        $stats = $response->json('stats');
        $this->assertEquals(1, $stats['flagged_artifacts']);
        $this->assertEquals(1, $stats['active_throttles']);
    }

    public function test_moderator_can_get_flagged_content(): void
    {
        // Create flagged and non-flagged artifacts
        $flaggedArtifact = ProximityArtifact::factory()->create([
            'is_flagged' => true,
            'flag_count' => 5,
        ]);
        ProximityArtifact::factory()->create(['is_flagged' => false]);

        $response = $this->withToken($this->moderatorToken)
            ->getJson('/api/moderation/flagged-content');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'artifact_type',
                        'content',
                        'is_flagged',
                        'flag_count',
                    ],
                ],
            ]);

        // Should only return flagged artifacts
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($flaggedArtifact->id, $response->json('data.0.id'));
    }

    public function test_moderator_can_review_flagged_artifact(): void
    {
        $artifact = ProximityArtifact::factory()->create([
            'is_flagged' => true,
            'flag_count' => 3,
        ]);

        $response = $this->withToken($this->moderatorToken)
            ->postJson("/api/moderation/flags/{$artifact->id}/review", [
                'action' => 'remove',
                'reason' => 'Violates community guidelines',
            ]);

        $response->assertOk();

        // Artifact should be soft deleted
        $this->assertSoftDeleted('proximity_artifacts', ['id' => $artifact->id]);

        // Moderation action should be logged
        $this->assertDatabaseHas('moderation_actions', [
            'moderator_id' => $this->moderator->id,
            'target_artifact_id' => $artifact->id,
            'action_type' => 'artifact_removed',
        ]);
    }

    public function test_moderator_can_dismiss_flag(): void
    {
        $artifact = ProximityArtifact::factory()->create([
            'is_flagged' => true,
            'flag_count' => 2,
        ]);

        $response = $this->withToken($this->moderatorToken)
            ->postJson("/api/moderation/flags/{$artifact->id}/review", [
                'action' => 'dismiss',
                'reason' => 'No violation found',
            ]);

        $response->assertOk();

        // Artifact should still exist but no longer flagged
        $artifact->refresh();
        $this->assertFalse($artifact->is_flagged);

        $this->assertDatabaseHas('moderation_actions', [
            'moderator_id' => $this->moderator->id,
            'target_artifact_id' => $artifact->id,
            'action_type' => 'flag_dismissed',
        ]);
    }

    public function test_moderator_can_get_spoof_detections(): void
    {
        $detection = GeoSpoofDetection::create([
            'user_id' => $this->regularUser->id,
            'ip_address' => '8.8.8.8',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'ip_latitude' => 37.7749,
            'ip_longitude' => -122.4194,
            'distance_km' => 4000,
            'velocity_kmh' => 15000,
            'suspicion_score' => 85,
            'detection_flags' => ['impossible_velocity', 'ip_distance_extreme'],
            'is_confirmed_spoof' => false,
            'detected_at' => now(),
        ]);

        $response = $this->withToken($this->moderatorToken)
            ->getJson('/api/moderation/spoof-detections');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'suspicion_score',
                        'detection_flags',
                        'is_confirmed_spoof',
                    ],
                ],
            ]);

        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_moderator_can_confirm_spoof(): void
    {
        $detection = GeoSpoofDetection::create([
            'user_id' => $this->regularUser->id,
            'ip_address' => '8.8.8.8',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'suspicion_score' => 90,
            'detection_flags' => ['impossible_velocity'],
            'is_confirmed_spoof' => false,
            'detected_at' => now(),
        ]);

        $response = $this->withToken($this->moderatorToken)
            ->postJson("/api/moderation/spoofs/{$detection->id}/review", [
                'action' => 'confirm',
                'reason' => 'Clear evidence of location manipulation',
                'apply_throttle' => true,
                'throttle_severity' => 4,
            ]);

        $response->assertOk();

        // Detection should be marked as confirmed
        $detection->refresh();
        $this->assertTrue($detection->is_confirmed_spoof);

        // Shadow throttle should be applied
        $this->assertDatabaseHas('shadow_throttles', [
            'user_id' => $this->regularUser->id,
            'severity' => 4,
        ]);

        // Moderation action logged
        $this->assertDatabaseHas('moderation_actions', [
            'moderator_id' => $this->moderator->id,
            'target_user_id' => $this->regularUser->id,
            'action_type' => 'spoof_confirmed',
        ]);
    }

    public function test_moderator_can_dismiss_spoof(): void
    {
        $detection = GeoSpoofDetection::create([
            'user_id' => $this->regularUser->id,
            'ip_address' => '8.8.8.8',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'suspicion_score' => 65,
            'detection_flags' => ['ip_distance_moderate'],
            'is_confirmed_spoof' => false,
            'detected_at' => now(),
        ]);

        $response = $this->withToken($this->moderatorToken)
            ->postJson("/api/moderation/spoofs/{$detection->id}/review", [
                'action' => 'dismiss',
                'reason' => 'Legitimate VPN use',
            ]);

        $response->assertOk();

        // Detection should be deleted or marked as reviewed
        $this->assertDatabaseMissing('geo_spoof_detections', [
            'id' => $detection->id,
            'is_confirmed_spoof' => false,
        ]);
    }

    public function test_moderator_can_get_active_throttles(): void
    {
        $throttleService = app(ShadowThrottleService::class);
        
        // Create active throttle
        $throttleService->applyThrottle(
            $this->regularUser->id,
            'Test throttle',
            3,
            24,
            $this->moderator->id
        );

        $response = $this->withToken($this->moderatorToken)
            ->getJson('/api/moderation/throttles');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'reason',
                        'severity',
                        'visibility_reduction',
                        'expires_at',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_moderator_can_remove_throttle(): void
    {
        $throttle = ShadowThrottle::create([
            'user_id' => $this->regularUser->id,
            'reason' => 'Test throttle',
            'severity' => 2,
            'visibility_reduction' => 0.50,
            'started_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->withToken($this->moderatorToken)
            ->deleteJson("/api/moderation/throttles/{$throttle->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('shadow_throttles', ['id' => $throttle->id]);

        $this->assertDatabaseHas('moderation_actions', [
            'moderator_id' => $this->moderator->id,
            'action_type' => 'throttle_removed',
        ]);
    }

    public function test_moderator_can_get_action_history(): void
    {
        ModerationAction::create([
            'moderator_id' => $this->moderator->id,
            'target_user_id' => $this->regularUser->id,
            'action_type' => 'flag_dismissed',
            'reason' => 'No violation',
        ]);

        $response = $this->withToken($this->moderatorToken)
            ->getJson('/api/moderation/actions');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'moderator_id',
                        'action_type',
                        'reason',
                        'created_at',
                    ],
                ],
            ]);

        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_moderator_can_get_user_profile_for_review(): void
    {
        $response = $this->withToken($this->moderatorToken)
            ->getJson("/api/moderation/users/{$this->regularUser->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'email', 'created_at'],
                'moderation_stats' => [
                    'total_flags_received',
                    'active_throttles',
                    'confirmed_spoofs',
                    'moderation_actions',
                ],
            ]);
    }
}
