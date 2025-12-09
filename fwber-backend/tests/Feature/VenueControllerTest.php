<?php

namespace Tests\Feature;

use App\Models\Venue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VenueControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Register trig functions for SQLite
        if (DB::connection()->getDriverName() === 'sqlite') {
            $db = DB::connection()->getPdo();
            $db->sqliteCreateFunction('acos', 'acos', 1);
            $db->sqliteCreateFunction('cos', 'cos', 1);
            $db->sqliteCreateFunction('sin', 'sin', 1);
            $db->sqliteCreateFunction('radians', 'deg2rad', 1);
            $db->sqliteCreateFunction('sqrt', 'sqrt', 1);
            $db->sqliteCreateFunction('atan2', 'atan2', 2);
        }
    }

    public function test_can_list_nearby_venues()
    {
        $user = User::factory()->create();
        
        // Venue nearby (New York)
        $nearbyVenue = Venue::factory()->create([
            'name' => 'Nearby Bar',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'is_active' => true,
        ]);

        // Venue far away (London)
        $farVenue = Venue::factory()->create([
            'name' => 'Far Pub',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/venues?lat=40.7128&lng=-74.0060&radius=5000');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Nearby Bar'])
            ->assertJsonMissing(['name' => 'Far Pub']);
    }

    public function test_can_view_venue_details()
    {
        $user = User::factory()->create();
        $venue = Venue::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/venues/{$venue->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => $venue->name]);
    }
}
