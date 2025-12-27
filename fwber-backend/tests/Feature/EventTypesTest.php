<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EventTypesTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_event_with_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/events', [
            'title' => 'Speed Dating Night',
            'description' => 'Find your match!',
            'type' => 'speed_dating',
            'location_name' => 'Downtown Bar',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'starts_at' => now()->addDay()->toIso8601String(),
            'ends_at' => now()->addDay()->addHours(2)->toIso8601String(),
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['type' => 'speed_dating']);

        $this->assertDatabaseHas('events', [
            'title' => 'Speed Dating Night',
            'type' => 'speed_dating',
        ]);
    }

    public function test_can_filter_events_by_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Event::factory()->create([
            'title' => 'Standard Event',
            'type' => 'standard',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'starts_at' => now()->addDay(),
        ]);

        Event::factory()->create([
            'title' => 'Party Event',
            'type' => 'party',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'starts_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/events?type=party');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Party Event'])
            ->assertJsonMissing(['title' => 'Standard Event']);
    }

    public function test_invalid_event_type_is_rejected()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/events', [
            'title' => 'Invalid Type Event',
            'description' => 'This should fail',
            'type' => 'invalid_type',
            'location_name' => 'Nowhere',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'starts_at' => now()->addDay()->toIso8601String(),
            'ends_at' => now()->addDay()->addHours(2)->toIso8601String(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }
}
