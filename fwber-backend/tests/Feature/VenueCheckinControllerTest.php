<?php

namespace Tests\Feature;

use App\Models\Venue;
use App\Models\VenueCheckin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueCheckinControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_check_in_when_close()
    {
        $user = User::factory()->create();
        $venue = Venue::factory()->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        // User is at the exact same location
        $response = $this->actingAs($user)
            ->postJson("/api/venues/{$venue->id}/checkin", [
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'message' => 'Hello world',
            ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('venue_checkins', [
            'user_id' => $user->id,
            'venue_id' => $venue->id,
            'message' => 'Hello world',
            'checked_out_at' => null,
        ]);
    }

    public function test_user_cannot_check_in_when_far()
    {
        $user = User::factory()->create();
        $venue = Venue::factory()->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        // User is far away (London)
        $response = $this->actingAs($user)
            ->postJson("/api/venues/{$venue->id}/checkin", [
                'latitude' => 51.5074,
                'longitude' => -0.1278,
            ]);

        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'You are too far from the venue to check in.']);
    }

    public function test_check_in_auto_checks_out_from_previous_venue()
    {
        $user = User::factory()->create();
        $venue1 = Venue::factory()->create(['latitude' => 40.7128, 'longitude' => -74.0060]);
        $venue2 = Venue::factory()->create(['latitude' => 40.7130, 'longitude' => -74.0060]); // Close enough to check in

        // Check in to venue 1
        VenueCheckin::create([
            'user_id' => $user->id,
            'venue_id' => $venue1->id,
            'created_at' => now()->subHour(),
        ]);

        // Check in to venue 2
        $response = $this->actingAs($user)
            ->postJson("/api/venues/{$venue2->id}/checkin", [
                'latitude' => 40.7130,
                'longitude' => -74.0060,
            ]);

        $response->assertStatus(201);

        // Verify venue 1 is checked out
        $this->assertDatabaseHas('venue_checkins', [
            'venue_id' => $venue1->id,
            'user_id' => $user->id,
        ]);
        
        $checkin1 = VenueCheckin::where('venue_id', $venue1->id)->first();
        $this->assertNotNull($checkin1->checked_out_at);
    }

    public function test_user_can_check_out()
    {
        $user = User::factory()->create();
        $venue = Venue::factory()->create();

        VenueCheckin::create([
            'user_id' => $user->id,
            'venue_id' => $venue->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/venues/{$venue->id}/checkout");

        $response->assertStatus(200);

        $checkin = VenueCheckin::where('venue_id', $venue->id)->first();
        $this->assertNotNull($checkin->checked_out_at);
    }

    public function test_can_get_current_checkin()
    {
        $user = User::factory()->create();
        $venue = Venue::factory()->create();

        VenueCheckin::create([
            'user_id' => $user->id,
            'venue_id' => $venue->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/user/checkin");

        $response->assertStatus(200)
            ->assertJsonFragment(['venue_id' => $venue->id]);
    }
}
