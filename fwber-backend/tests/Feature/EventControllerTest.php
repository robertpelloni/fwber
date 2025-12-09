<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_events()
    {
        $user = User::factory()->create();
        Event::factory()->count(3)->create([
            'created_by_user_id' => $user->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/events?latitude=40.7128&longitude=-74.0060&radius=10');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_event()
    {
        $user = User::factory()->create();

        $eventData = [
            'title' => 'Test Event',
            'description' => 'This is a test event',
            'location_name' => 'Central Park',
            'latitude' => 40.7851,
            'longitude' => -73.9683,
            'starts_at' => now()->addDays(1)->toIso8601String(),
            'ends_at' => now()->addDays(1)->addHours(2)->toIso8601String(),
            'max_attendees' => 50,
            'price' => 0,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/events', $eventData);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'Test Event']);

        $this->assertDatabaseHas('events', ['title' => 'Test Event']);
    }

    public function test_can_rsvp_to_event()
    {
        $creator = User::factory()->create();
        $attendee = User::factory()->create();
        $event = Event::factory()->create(['created_by_user_id' => $creator->id]);

        $response = $this->actingAs($attendee)
            ->postJson("/api/events/{$event->id}/rsvp", ['status' => 'attending']);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('event_attendees', [
            'event_id' => $event->id,
            'user_id' => $attendee->id,
            'status' => 'attending'
        ]);
    }
}
