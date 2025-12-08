<?php

namespace Tests\Feature;

use App\Models\ProximityChatroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProximityChatroomTest extends TestCase
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

    public function test_can_create_proximity_chatroom()
    {
        $user = User::factory()->create();
        
        $chatroom = ProximityChatroom::factory()->create([
            'created_by' => $user->id,
            'name' => 'Test Area',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'radius_meters' => 1000,
        ]);

        $this->assertDatabaseHas('proximity_chatrooms', [
            'id' => $chatroom->id,
            'name' => 'Test Area',
        ]);
    }

    public function test_can_add_member()
    {
        $chatroom = ProximityChatroom::factory()->create();
        $user = User::factory()->create();

        $chatroom->addMember($user, [
            'latitude' => $chatroom->latitude,
            'longitude' => $chatroom->longitude,
        ]);

        $this->assertDatabaseHas('proximity_chatroom_members', [
            'proximity_chatroom_id' => $chatroom->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($chatroom->hasMember($user));
    }

    public function test_proximity_logic()
    {
        $chatroom = ProximityChatroom::factory()->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'radius_meters' => 1000,
        ]);

        // Point within 1000m (approx)
        // 0.001 degree lat is approx 111m
        $nearbyLat = 40.7128 + 0.001; 
        $nearbyLng = -74.0060;

        $this->assertTrue($chatroom->isWithinProximity($nearbyLat, $nearbyLng));

        // Point far away
        $farLat = 41.0;
        $farLng = -74.0;

        $this->assertFalse($chatroom->isWithinProximity($farLat, $farLng));
    }

    public function test_find_nearby_scope()
    {
        // Create a chatroom in NYC
        $nycChatroom = ProximityChatroom::factory()->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'radius_meters' => 5000,
        ]);

        // Create a chatroom in London
        $londonChatroom = ProximityChatroom::factory()->create([
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'radius_meters' => 5000,
        ]);

        // Search near NYC
        $nearby = ProximityChatroom::findNearby(40.7128, -74.0060, 10000);

        $this->assertTrue($nearby->contains($nycChatroom));
        $this->assertFalse($nearby->contains($londonChatroom));
    }
}
