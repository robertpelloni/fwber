<?php

namespace Tests\Feature;

use App\Models\Friend;
use App\Models\Group;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GroupRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_matches_include_ranking_strategy_metadata(): void
    {
        $viewer = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $viewer->id,
            'latitude' => 42.3314,
            'longitude' => -83.0458,
        ]);

        $sourceGroup = Group::factory()->create([
            'created_by_user_id' => $viewer->id,
            'creator_id' => $viewer->id,
            'matching_enabled' => true,
            'location_lat' => 42.3314,
            'location_lon' => -83.0458,
            'category' => 'culture',
            'tags' => ['coffee'],
        ]);
        $sourceGroup->members()->create([
            'user_id' => $viewer->id,
            'role' => 'owner',
            'is_active' => true,
            'is_banned' => false,
            'joined_at' => now(),
        ]);

        $candidateOwner = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $candidateOwner->id]);
        $candidateGroup = Group::factory()->create([
            'created_by_user_id' => $candidateOwner->id,
            'creator_id' => $candidateOwner->id,
            'matching_enabled' => true,
            'location_lat' => 42.3320,
            'location_lon' => -83.0460,
            'category' => 'culture',
            'tags' => ['coffee'],
        ]);
        $candidateGroup->members()->create([
            'user_id' => $candidateOwner->id,
            'role' => 'owner',
            'is_active' => true,
            'is_banned' => false,
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson("/api/groups/{$sourceGroup->id}/matches?radius=50&ranking_strategy=trust-aware");

        $response->assertOk()
            ->assertJsonPath('meta.ranking_strategy.compatibility', true)
            ->assertJsonPath('meta.ranking_strategy.trusted_members', true)
            ->assertJsonPath('meta.ranking_strategy.scene_alignment', true)
            ->assertJsonPath('meta.ranking_strategy.member_health', true)
            ->assertJsonPath('meta.ranking_strategy.distance', true)
            ->assertJsonCount(1, 'matches');
    }

    public function test_trusted_scene_aligned_group_can_outrank_a_closer_stranger_group(): void
    {
        $viewer = User::factory()->create();
        $friend = User::factory()->create();
        $stranger = User::factory()->create();

        UserProfile::factory()->create([
            'user_id' => $viewer->id,
            'latitude' => 42.3314,
            'longitude' => -83.0458,
            'interests' => ['coffee', 'nightlife'],
        ]);
        UserProfile::factory()->create([
            'user_id' => $friend->id,
            'is_verified' => true,
            'is_id_verified' => true,
        ]);
        UserProfile::factory()->create([
            'user_id' => $stranger->id,
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

        $sourceGroup = Group::factory()->create([
            'created_by_user_id' => $viewer->id,
            'creator_id' => $viewer->id,
            'matching_enabled' => true,
            'location_lat' => 42.3314,
            'location_lon' => -83.0458,
            'category' => 'culture',
            'tags' => ['coffee', 'nightlife'],
            'member_count' => 4,
        ]);
        $sourceGroup->members()->create([
            'user_id' => $viewer->id,
            'role' => 'owner',
            'is_active' => true,
            'is_banned' => false,
            'joined_at' => now(),
        ]);

        $closerGroup = Group::factory()->create([
            'name' => 'Generic Nearby Group',
            'created_by_user_id' => $stranger->id,
            'creator_id' => $stranger->id,
            'matching_enabled' => true,
            'location_lat' => 42.33145,
            'location_lon' => -83.04575,
            'category' => 'culture',
            'tags' => ['coffee'],
            'member_count' => 4,
        ]);
        $closerGroup->members()->create([
            'user_id' => $stranger->id,
            'role' => 'owner',
            'is_active' => true,
            'is_banned' => false,
            'joined_at' => now(),
        ]);

        $trustedGroup = Group::factory()->create([
            'name' => 'Warehouse Coffee Collective',
            'description' => 'Coffee hangs before the warehouse nights start.',
            'created_by_user_id' => $friend->id,
            'creator_id' => $friend->id,
            'matching_enabled' => true,
            'location_lat' => 42.3380,
            'location_lon' => -83.0520,
            'category' => 'culture',
            'tags' => ['coffee'],
            'member_count' => 4,
        ]);
        $trustedGroup->members()->create([
            'user_id' => $friend->id,
            'role' => 'owner',
            'is_active' => true,
            'is_banned' => false,
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson("/api/groups/{$sourceGroup->id}/matches?radius=50&ranking_strategy=trust-aware");

        $response->assertOk()
            ->assertJsonPath('matches.0.name', 'Warehouse Coffee Collective')
            ->assertJsonPath('matches.0.scene_signals.matched_topics.0.slug', 'warehouse-nights');
    }
}
