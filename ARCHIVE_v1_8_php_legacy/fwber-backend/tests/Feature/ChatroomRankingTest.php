<?php

namespace Tests\Feature;

use App\Models\Chatroom;
use App\Models\Friend;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatroomRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_chatrooms_include_ranking_strategy_metadata(): void
    {
        $viewer = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $viewer->id,
            'interests' => ['coffee'],
        ]);

        $creator = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $creator->id,
            'bio' => 'Coffee and downtown chats.',
        ]);

        Chatroom::factory()->create([
            'created_by' => $creator->id,
            'name' => 'Morning Coffee Circle',
            'description' => 'Discuss coffee and downtown scenes.',
            'category' => 'coffee',
            'is_public' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($viewer)->getJson('/api/chatrooms?ranking_strategy=trust-aware');

        $response->assertOk()
            ->assertJsonPath('meta.ranking_strategy.trusted_creators', true)
            ->assertJsonPath('meta.ranking_strategy.scene_alignment', true)
            ->assertJsonPath('meta.ranking_strategy.community_health', true)
            ->assertJsonPath('meta.ranking_strategy.freshness', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_trusted_scene_aligned_chatroom_can_outrank_a_busier_generic_room(): void
    {
        $viewer = User::factory()->create();
        $friendCreator = User::factory()->create();
        $strangerCreator = User::factory()->create();

        UserProfile::factory()->create([
            'user_id' => $viewer->id,
            'interests' => ['coffee', 'warehouse'],
        ]);
        UserProfile::factory()->create([
            'user_id' => $friendCreator->id,
            'bio' => 'Warehouse coffee chats before the party starts.',
        ]);
        UserProfile::factory()->create([
            'user_id' => $strangerCreator->id,
            'bio' => 'General discussion.',
        ]);

        Friend::factory()->create([
            'user_id' => $viewer->id,
            'friend_id' => $friendCreator->id,
            'status' => 'accepted',
        ]);
        Friend::factory()->create([
            'user_id' => $friendCreator->id,
            'friend_id' => $viewer->id,
            'status' => 'accepted',
        ]);

        Chatroom::factory()->create([
            'created_by' => $strangerCreator->id,
            'name' => 'Generic Community Chat',
            'description' => 'General local discussion for everyone.',
            'category' => 'general',
            'member_count' => 10,
            'message_count' => 80,
            'last_activity_at' => now()->subMinutes(5),
            'created_at' => now()->subHours(2),
            'is_public' => true,
            'is_active' => true,
        ]);

        Chatroom::factory()->create([
            'created_by' => $friendCreator->id,
            'name' => 'Warehouse Coffee Collective',
            'description' => 'Coffee chats and warehouse scene meetups.',
            'category' => 'coffee',
            'member_count' => 8,
            'message_count' => 55,
            'last_activity_at' => now()->subMinutes(12),
            'created_at' => now()->subHour(),
            'is_public' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($viewer)->getJson('/api/chatrooms?ranking_strategy=trust-aware&sort=most_active');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Warehouse Coffee Collective')
            ->assertJsonPath('data.0.scene_signals.matched_tags.0', 'coffee');
    }
}
