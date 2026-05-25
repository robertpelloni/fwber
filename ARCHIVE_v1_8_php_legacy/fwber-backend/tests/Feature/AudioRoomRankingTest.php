<?php

namespace Tests\Feature;

use App\Models\AudioRoom;
use App\Models\AudioRoomParticipant;
use App\Models\Friend;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserLocation;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AudioRoomRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_audio_rooms_include_ranking_strategy_metadata(): void
    {
        $viewer = User::factory()->create();
        $host = User::factory()->create();

        UserProfile::factory()->create(['user_id' => $viewer->id]);
        UserProfile::factory()->create(['user_id' => $host->id]);
        UserLocation::create([
            'user_id' => $host->id,
            'latitude' => 40.7130,
            'longitude' => -74.0060,
            'privacy_level' => 'public',
            'last_updated' => now(),
            'is_active' => true,
        ]);

        AudioRoom::create([
            'host_id' => $host->id,
            'name' => 'Late Night Stage',
            'topic' => 'Nightlife planning',
            'status' => 'active',
        ]);

        $response = $this->actingAs($viewer)->getJson('/api/audio-rooms?ranking_strategy=trust-aware&latitude=40.7128&longitude=-74.0060');

        $response->assertOk()
            ->assertJsonPath('meta.ranking_strategy.trusted_hosts', true)
            ->assertJsonPath('meta.ranking_strategy.scene_alignment', true)
            ->assertJsonPath('meta.ranking_strategy.participant_health', true)
            ->assertJsonPath('meta.ranking_strategy.freshness', true)
            ->assertJsonPath('meta.ranking_strategy.distance', true)
            ->assertJsonCount(1, 'rooms');
    }

    public function test_trusted_scene_aligned_room_can_outrank_a_closer_stranger_room(): void
    {
        $viewer = User::factory()->create();
        $friendHost = User::factory()->create();
        $strangerHost = User::factory()->create();

        UserProfile::factory()->create([
            'user_id' => $viewer->id,
            'interests' => ['coffee', 'nightlife'],
        ]);
        UserProfile::factory()->create([
            'user_id' => $friendHost->id,
            'bio' => 'Warehouse coffee and nightlife host.',
            'interests' => ['warehouse', 'coffee', 'nightlife'],
        ]);
        UserProfile::factory()->create([
            'user_id' => $strangerHost->id,
            'bio' => 'General chat host.',
            'interests' => ['casual'],
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
            'friend_id' => $friendHost->id,
            'status' => 'accepted',
        ]);
        Friend::factory()->create([
            'user_id' => $friendHost->id,
            'friend_id' => $viewer->id,
            'status' => 'accepted',
        ]);

        UserLocation::create([
            'user_id' => $strangerHost->id,
            'latitude' => 40.7129,
            'longitude' => -74.0060,
            'privacy_level' => 'public',
            'last_updated' => now(),
            'is_active' => true,
        ]);
        UserLocation::create([
            'user_id' => $friendHost->id,
            'latitude' => 40.7143,
            'longitude' => -74.0065,
            'privacy_level' => 'public',
            'last_updated' => now(),
            'is_active' => true,
        ]);

        $closerRoom = AudioRoom::create([
            'host_id' => $strangerHost->id,
            'name' => 'Nearby Open Mic',
            'topic' => 'Casual small talk',
            'status' => 'active',
        ]);
        AudioRoomParticipant::create([
            'audio_room_id' => $closerRoom->id,
            'user_id' => $strangerHost->id,
            'role' => 'speaker',
            'is_muted' => false,
        ]);

        $trustedRoom = AudioRoom::create([
            'host_id' => $friendHost->id,
            'name' => 'Warehouse Coffee Pre-Game',
            'topic' => 'Warehouse nightlife and coffee',
            'status' => 'active',
        ]);
        AudioRoomParticipant::create([
            'audio_room_id' => $trustedRoom->id,
            'user_id' => $friendHost->id,
            'role' => 'speaker',
            'is_muted' => false,
        ]);
        AudioRoomParticipant::create([
            'audio_room_id' => $trustedRoom->id,
            'user_id' => User::factory()->create()->id,
            'role' => 'listener',
            'is_muted' => true,
        ]);

        $response = $this->actingAs($viewer)->getJson('/api/audio-rooms?ranking_strategy=trust-aware&latitude=40.7128&longitude=-74.0060');

        $response->assertOk()
            ->assertJsonPath('rooms.0.id', $trustedRoom->id)
            ->assertJsonPath('rooms.0.scene_signals.matched_topics.0.slug', 'warehouse-nights');
    }
}
