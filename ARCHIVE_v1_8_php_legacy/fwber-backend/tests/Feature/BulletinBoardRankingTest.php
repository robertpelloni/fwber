<?php

namespace Tests\Feature;

use App\Models\BulletinBoard;
use App\Models\BulletinMessage;
use App\Models\Friend;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BulletinBoardRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulletin_boards_include_ranking_strategy_metadata(): void
    {
        $viewer = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $viewer->id,
            'latitude' => 42.3314,
            'longitude' => -83.0458,
        ]);

        $board = BulletinBoard::factory()->create([
            'center_lat' => 42.3314,
            'center_lng' => -83.0458,
        ]);

        BulletinMessage::create([
            'bulletin_board_id' => $board->id,
            'user_id' => $viewer->id,
            'content' => 'Neighborhood hello world.',
            'is_moderated' => false,
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/bulletin-boards?lat=42.3314&lng=-83.0458&radius=1000&ranking_strategy=trust-aware');

        $response->assertOk()
            ->assertJsonPath('meta.ranking_strategy.trusted_participants', true)
            ->assertJsonPath('meta.ranking_strategy.scene_alignment', true)
            ->assertJsonPath('meta.ranking_strategy.freshness', true)
            ->assertJsonPath('meta.ranking_strategy.activity_health', true)
            ->assertJsonPath('meta.ranking_strategy.distance', true)
            ->assertJsonCount(1, 'boards');
    }

    public function test_trusted_scene_aligned_board_can_outrank_a_closer_stranger_board(): void
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

        $closerBoard = BulletinBoard::factory()->create([
            'name' => 'Generic Nearby Board',
            'description' => 'General local updates.',
            'center_lat' => 42.33145,
            'center_lng' => -83.04575,
            'active_users' => 1,
        ]);
        BulletinMessage::create([
            'bulletin_board_id' => $closerBoard->id,
            'user_id' => $stranger->id,
            'content' => 'Hello from a generic local board.',
            'is_moderated' => false,
        ]);
        $closerBoard->updateActivity();

        $trustedBoard = BulletinBoard::factory()->create([
            'name' => 'Warehouse Coffee Board',
            'description' => 'Coffee meetups before the warehouse set tonight.',
            'center_lat' => 42.3322,
            'center_lng' => -83.0464,
            'active_users' => 3,
        ]);
        BulletinMessage::create([
            'bulletin_board_id' => $trustedBoard->id,
            'user_id' => $friend->id,
            'content' => 'Warehouse coffee meetup before nightlife starts.',
            'is_moderated' => false,
        ]);
        $trustedBoard->updateActivity();

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/bulletin-boards?lat=42.3314&lng=-83.0458&radius=1000&ranking_strategy=trust-aware');

        $response->assertOk()
            ->assertJsonPath('boards.0.name', 'Warehouse Coffee Board')
            ->assertJsonPath('boards.0.scene_signals.matched_topics.0.slug', 'warehouse-nights');
    }
}
