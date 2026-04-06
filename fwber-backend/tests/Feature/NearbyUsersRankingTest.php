<?php

namespace Tests\Feature;

use App\Models\Friend;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserLocation;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NearbyUsersRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_nearby_users_include_ranking_strategy_metadata(): void
    {
        $viewer = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $viewer->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $candidate = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $candidate->id,
            'display_name' => 'Nearby Friend',
            'is_incognito' => false,
        ]);
        UserLocation::create([
            'user_id' => $candidate->id,
            'latitude' => 40.7130,
            'longitude' => -74.0060,
            'privacy_level' => 'public',
            'last_updated' => now(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($viewer)->getJson('/api/location/nearby?latitude=40.7128&longitude=-74.0060&radius=1000&ranking_strategy=trust-aware');

        $response->assertOk()
            ->assertJsonPath('meta.ranking_strategy.trust_connections', true)
            ->assertJsonPath('meta.ranking_strategy.scene_alignment', true)
            ->assertJsonPath('meta.ranking_strategy.activity_recency', true)
            ->assertJsonPath('meta.ranking_strategy.distance', true)
            ->assertJsonPath('data.0.display_name', 'Nearby Friend');
    }

    public function test_trusted_scene_aligned_user_can_outrank_a_closer_stranger(): void
    {
        $viewer = User::factory()->create();
        $friend = User::factory()->create();
        $stranger = User::factory()->create();

        UserProfile::factory()->create([
            'user_id' => $viewer->id,
            'interests' => ['coffee', 'nightlife'],
        ]);
        UserProfile::factory()->create([
            'user_id' => $friend->id,
            'display_name' => 'Warehouse Coffee Friend',
            'bio' => 'Warehouse coffee before nightlife starts.',
            'interests' => ['coffee', 'warehouse', 'nightlife'],
            'is_incognito' => false,
        ]);
        UserProfile::factory()->create([
            'user_id' => $stranger->id,
            'display_name' => 'Generic Nearby Stranger',
            'bio' => 'Just around the block.',
            'interests' => ['walks'],
            'is_incognito' => false,
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
        $viewer->followedTopics()->attach($topic->id, ['followed_at' => now()]);

        Friend::factory()->create([
            'user_id' => $viewer->id,
            'friend_id' => $friend->id,
            'status' => 'accepted',
        ]);
        Friend::factory()->create([
            'user_id' => $friend->id,
            'friend_id' => $viewer->id,
            'status' => 'accepted',
        ]);

        UserLocation::create([
            'user_id' => $stranger->id,
            'latitude' => 40.71295,
            'longitude' => -74.0060,
            'privacy_level' => 'public',
            'last_updated' => now()->subMinutes(2),
            'is_active' => true,
        ]);
        UserLocation::create([
            'user_id' => $friend->id,
            'latitude' => 40.7142,
            'longitude' => -74.0064,
            'privacy_level' => 'public',
            'last_updated' => now()->subMinute(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($viewer)->getJson('/api/location/nearby?latitude=40.7128&longitude=-74.0060&radius=1000&ranking_strategy=trust-aware');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $friend->id)
            ->assertJsonPath('data.0.scene_signals.matched_topics.0.slug', 'warehouse-nights');
    }
}
