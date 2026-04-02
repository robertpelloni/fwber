<?php

namespace Tests\Feature;

use App\Models\ProximityArtifact;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LocalPulseSceneSignalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_pulse_artifacts_include_scene_signals_for_followed_topics(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'latitude' => 42.3314,
            'longitude' => -83.0458,
            'interests' => ['coffee', 'nightlife'],
        ]);

        $topic = Topic::query()->firstOrCreate(
            ['slug' => 'warehouse-nights'],
            [
                'label' => 'Warehouse Nights',
                'description' => 'Late nights and underground rooms.',
                'emoji' => '🌃',
                'category' => 'culture',
                'aliases' => ['warehouse', 'nightlife'],
                'is_featured' => true,
                'sort_order' => 99,
            ]
        );

        $user->followedTopics()->attach($topic->id, ['followed_at' => now()]);

        ProximityArtifact::factory()->create([
            'user_id' => $user->id,
            'content' => 'Coffee meetup before the warehouse set tonight.',
            'location_lat' => 42.3315,
            'location_lng' => -83.0457,
            'meta' => ['topic_slug' => 'warehouse-nights'],
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/proximity/local-pulse?lat=42.3314&lng=-83.0458&radius=1000');

        $response->assertOk()
            ->assertJsonPath('artifacts.0.scene_signals.matched_topics.0.slug', 'warehouse-nights');

        $this->assertGreaterThan(0, $response->json('artifacts.0.scene_signals.score_boost'));
        $matchedTags = $response->json('artifacts.0.scene_signals.matched_tags');

        $this->assertContains('coffee', $matchedTags);
        $this->assertContains('warehouse', $matchedTags);
    }
}
