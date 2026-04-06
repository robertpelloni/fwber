<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Friend;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_include_ranking_strategy_metadata(): void
    {
        $viewer = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $viewer->id,
            'latitude' => 42.3314,
            'longitude' => -83.0458,
        ]);

        Event::factory()->create([
            'created_by_user_id' => $viewer->id,
            'latitude' => 42.3314,
            'longitude' => -83.0458,
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/events?latitude=42.3314&longitude=-83.0458&radius=10&ranking_strategy=trust-aware');

        $response->assertOk()
            ->assertJsonPath('meta.ranking_strategy.trusted_connections', true)
            ->assertJsonPath('meta.ranking_strategy.scene_alignment', true)
            ->assertJsonPath('meta.ranking_strategy.freshness', true)
            ->assertJsonPath('meta.ranking_strategy.distance', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_trusted_scene_aligned_event_can_outrank_a_closer_stranger(): void
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

        Event::factory()->create([
            'created_by_user_id' => $stranger->id,
            'title' => 'Generic Nearby Event',
            'description' => 'Just a general meetup.',
            'location_name' => 'Corner Cafe',
            'latitude' => 42.33145,
            'longitude' => -83.04575,
            'starts_at' => now()->addHours(2),
        ]);

        Event::factory()->create([
            'created_by_user_id' => $friend->id,
            'title' => 'Warehouse Coffee Collective',
            'description' => 'Coffee meetup before the warehouse set tonight.',
            'location_name' => 'Warehouse District',
            'type' => 'meetup',
            'latitude' => 42.3322,
            'longitude' => -83.0464,
            'starts_at' => now()->addHours(4),
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/events?latitude=42.3314&longitude=-83.0458&radius=10&ranking_strategy=trust-aware');

        $response->assertOk()
            ->assertJsonPath('data.0.title', 'Warehouse Coffee Collective')
            ->assertJsonPath('data.0.scene_signals.matched_topics.0.slug', 'warehouse-nights');
    }
}
