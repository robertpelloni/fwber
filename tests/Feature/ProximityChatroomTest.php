<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\ProximityChatroom;
use App\Models\User;
use App\Models\UserProfile;
use Tests\Traits\RefreshDatabaseSilently;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProximityChatroomTest extends TestCase
{
    use RefreshDatabaseSilently;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite') {
            $db = \Illuminate\Support\Facades\DB::connection()->getPdo();
            $db->sqliteCreateFunction('acos', 'acos', 1);
            $db->sqliteCreateFunction('cos', 'cos', 1);
            $db->sqliteCreateFunction('radians', function ($deg) {
                return deg2rad($deg);
            }, 1);
            $db->sqliteCreateFunction('sin', 'sin', 1);
        }

        // Enable feature flag
        config(['features.proximity_chatrooms' => true]);

        $this->user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $this->user->id,
            'display_name' => 'Tester',
            'date_of_birth' => now()->subYears(25),
            'gender' => 'other',
            'location_latitude' => 40.0,
            'location_longitude' => -74.0,
        ]);
        $this->token = ApiToken::generateForUser($this->user, 'test');
    }

    #[Test]
    public function create_proximity_chatroom(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/proximity-chatrooms', [
                'name' => 'Test Room',
                'type' => 'event',
                'latitude' => 40.0,
                'longitude' => -74.0,
                'radius_meters' => 500,
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('proximity_chatrooms', [
            'name' => 'Test Room',
            'type' => 'event',
            'created_by' => $this->user->id,
        ]);
    }

    #[Test]
    public function find_nearby_chatrooms(): void
    {
        // Create a chatroom nearby
        ProximityChatroom::create([
            'name' => 'Nearby Room',
            'type' => 'event',
            'latitude' => 40.001,
            'longitude' => -74.001,
            'radius_meters' => 500,
            'created_by' => $this->user->id,
            'geohash' => 'dr5r9ydj', // Dummy geohash
            'is_active' => true,
            'is_public' => true,
        ]);

        // Create a chatroom far away
        ProximityChatroom::create([
            'name' => 'Far Room',
            'type' => 'event',
            'latitude' => 41.0,
            'longitude' => -75.0,
            'radius_meters' => 500,
            'created_by' => $this->user->id,
            'geohash' => 'dr7r9ydj', // Dummy geohash
            'is_active' => true,
            'is_public' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/proximity-chatrooms/nearby?latitude=40.0&longitude=-74.0&radius_meters=1000');

        $response->assertOk();
        $response->assertJsonCount(1, 'chatrooms');
        $response->assertJsonFragment(['name' => 'Nearby Room']);
        $response->assertJsonMissing(['name' => 'Far Room']);
    }

    #[Test]
    public function join_proximity_chatroom(): void
    {
        $chatroom = ProximityChatroom::create([
            'name' => 'Joinable Room',
            'type' => 'event',
            'latitude' => 40.0,
            'longitude' => -74.0,
            'radius_meters' => 500,
            'created_by' => User::factory()->create()->id,
            'geohash' => 'dr5r9ydj',
            'is_active' => true,
            'is_public' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/proximity-chatrooms/{$chatroom->id}/join", [
                'latitude' => 40.001,
                'longitude' => -74.001,
            ]);

        $response->assertOk();
        
        $this->assertDatabaseHas('proximity_chatroom_members', [
            'proximity_chatroom_id' => $chatroom->id,
            'user_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function cannot_join_if_too_far(): void
    {
        $chatroom = ProximityChatroom::create([
            'name' => 'Far Room',
            'type' => 'event',
            'latitude' => 40.0,
            'longitude' => -74.0,
            'radius_meters' => 100, // Small radius
            'created_by' => User::factory()->create()->id,
            'geohash' => 'dr5r9ydj',
            'is_active' => true,
            'is_public' => true,
        ]);

        // User is far away (approx 11km)
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/proximity-chatrooms/{$chatroom->id}/join", [
                'latitude' => 40.1,
                'longitude' => -74.0,
            ]);

        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'You are not within the proximity of this chatroom']);
    }

    #[Test]
    public function leave_proximity_chatroom(): void
    {
        $chatroom = ProximityChatroom::create([
            'name' => 'Leavable Room',
            'type' => 'event',
            'latitude' => 40.0,
            'longitude' => -74.0,
            'radius_meters' => 500,
            'created_by' => User::factory()->create()->id,
            'geohash' => 'dr5r9ydj',
            'is_active' => true,
            'is_public' => true,
        ]);

        // Join first
        $chatroom->addMember($this->user, [
            'latitude' => 40.0,
            'longitude' => -74.0,
            'distance_meters' => 0,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/proximity-chatrooms/{$chatroom->id}/leave");

        $response->assertOk();
        
        $this->assertDatabaseMissing('proximity_chatroom_members', [
            'proximity_chatroom_id' => $chatroom->id,
            'user_id' => $this->user->id,
        ]);
        // Check if row is removed or marked as left. 
        // The controller uses removeMember which likely detaches or updates pivot.
        // Let's check if the user is no longer an active member.
        $this->assertFalse($chatroom->hasMember($this->user));
    }
}
