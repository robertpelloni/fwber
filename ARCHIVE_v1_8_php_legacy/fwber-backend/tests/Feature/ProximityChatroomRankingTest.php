<?php

namespace Tests\Feature;

use App\Models\Friend;
use App\Models\ProximityChatroom;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProximityChatroomRankingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() === 'sqlite') {
            $db = DB::connection()->getPdo();
            $db->sqliteCreateFunction('acos', 'acos', 1);
            $db->sqliteCreateFunction('cos', 'cos', 1);
            $db->sqliteCreateFunction('radians', 'deg2rad', 1);
            $db->sqliteCreateFunction('sin', 'sin', 1);
        }
    }

    public function test_nearby_chatrooms_include_ranking_strategy_metadata(): void
    {
        $viewer = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $viewer->id,
            'latitude' => 42.3314,
            'longitude' => -83.0458,
        ]);

        ProximityChatroom::factory()->create([
            'created_by' => $viewer->id,
            'latitude' => 42.3314,
            'longitude' => -83.0458,
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/proximity-chatrooms/nearby?latitude=42.3314&longitude=-83.0458&radius_meters=1000');

        $response->assertOk()
            ->assertJsonPath('meta.ranking_strategy.trusted_connections', true)
            ->assertJsonPath('meta.ranking_strategy.scene_alignment', true)
            ->assertJsonPath('meta.ranking_strategy.freshness', true)
            ->assertJsonPath('meta.ranking_strategy.distance', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_trusted_scene_aligned_chatroom_can_outrank_a_closer_stranger(): void
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

        ProximityChatroom::factory()->create([
            'created_by' => $stranger->id,
            'name' => 'Generic Nearby Room',
            'description' => 'Just a general hangout.',
            'latitude' => 42.33145,
            'longitude' => -83.04575,
            'last_activity_at' => now()->subMinute(),
        ]);

        ProximityChatroom::factory()->create([
            'created_by' => $friend->id,
            'name' => 'Warehouse Coffee Collective',
            'description' => 'Coffee meetup before the warehouse set tonight.',
            'tags' => ['warehouse', 'coffee'],
            'latitude' => 42.3322,
            'longitude' => -83.0464,
            'last_activity_at' => now()->subMinutes(10),
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/proximity-chatrooms/nearby?latitude=42.3314&longitude=-83.0458&radius_meters=1000');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Warehouse Coffee Collective')
            ->assertJsonPath('data.0.scene_signals.matched_topics.0.slug', 'warehouse-nights');
    }
}
