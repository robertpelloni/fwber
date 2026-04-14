<?php

namespace Tests\Feature;

use App\Models\AudioRoom;
use App\Models\AudioRoomParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AudioRoomTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_audio_room()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/audio-rooms', [
            'name' => 'Late Night Chats',
            'topic' => 'Just vibing',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id', 'name', 'topic', 'host_id',
            ]);

        $this->assertDatabaseHas('audio_rooms', [
            'name' => 'Late Night Chats',
            'topic' => 'Just vibing',
            'host_id' => $user->id,
        ]);

        $roomId = $response->json('id');

        $this->assertDatabaseHas('audio_room_participants', [
            'audio_room_id' => $roomId,
            'user_id' => $user->id,
            'role' => 'speaker',
        ]);
    }

    public function test_user_can_list_active_audio_rooms()
    {
        $user = User::factory()->create();

        // Active room
        AudioRoom::create([
            'host_id' => $user->id,
            'name' => 'Active Room',
            'topic' => 'Topic',
            'status' => 'active',
        ]);

        // Ended room
        AudioRoom::create([
            'host_id' => $user->id,
            'name' => 'Ended Room',
            'topic' => 'Topic',
            'status' => 'ended',
        ]);

        $response = $this->actingAs($user)->getJson('/api/audio-rooms');

        $response->assertStatus(200)
            ->assertJsonCount(1); // Only the active room should be listed

        $this->assertEquals('Active Room', $response->json('0.name'));
    }

    public function test_user_can_join_audio_room()
    {
        $host = User::factory()->create();
        $joiner = User::factory()->create();

        $room = AudioRoom::create([
            'host_id' => $host->id,
            'name' => 'Join Me',
            'topic' => 'Topic',
            'status' => 'active',
        ]);

        $response = $this->actingAs($joiner)->postJson("/api/audio-rooms/{$room->id}/join");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'participant' => ['id', 'user_id', 'role'],
            ]);

        $this->assertDatabaseHas('audio_room_participants', [
            'audio_room_id' => $room->id,
            'user_id' => $joiner->id,
            'role' => 'listener',
        ]);
    }

    public function test_user_can_leave_audio_room()
    {
        $host = User::factory()->create();
        $joiner = User::factory()->create();

        $room = AudioRoom::create([
            'host_id' => $host->id,
            'name' => 'Leave Me',
            'topic' => 'Topic',
            'status' => 'active',
        ]);

        AudioRoomParticipant::create([
            'audio_room_id' => $room->id,
            'user_id' => $joiner->id,
            'role' => 'listener',
        ]);

        $response = $this->actingAs($joiner)->postJson("/api/audio-rooms/{$room->id}/leave");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('audio_room_participants', [
            'audio_room_id' => $room->id,
            'user_id' => $joiner->id,
        ]);
    }

    public function test_user_can_send_webrtc_signal()
    {
        $host = User::factory()->create();
        $joiner = User::factory()->create();

        $room = AudioRoom::create([
            'host_id' => $host->id,
            'name' => 'Signal Room',
            'topic' => 'Topic',
            'status' => 'active',
        ]);

        $response = $this->actingAs($joiner)->postJson("/api/audio-rooms/{$room->id}/signal", [
            'target_user_id' => $host->id,
            'type' => 'offer',
            'payload' => 'sdp_data_here',
        ]);

        $response->assertStatus(200);
    }
}
