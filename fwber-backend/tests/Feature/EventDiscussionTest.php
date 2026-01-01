<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\Chatroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EventDiscussionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_event_creates_linked_chatroom()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/events', [
            'title' => 'Discussion Test Event',
            'description' => 'Testing chatroom creation',
            'location_name' => 'Test Location',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'starts_at' => now()->addDay()->toIso8601String(),
            'ends_at' => now()->addDay()->addHours(2)->toIso8601String(),
        ]);

        $response->assertStatus(201);
        
        $event = Event::first();
        $this->assertNotNull($event->chatroom_id);
        
        $chatroom = Chatroom::find($event->chatroom_id);
        $this->assertNotNull($chatroom);
        $this->assertEquals($event->title, $chatroom->name);
        $this->assertEquals('event', $chatroom->type);
        
        // Creator should be a member/admin
        $this->assertTrue($chatroom->hasMember($user));
        $this->assertTrue($chatroom->hasModerator($user));
    }

    public function test_rsvping_adds_user_to_chatroom()
    {
        $creator = User::factory()->create();
        $attendee = User::factory()->create();
        
        $this->actingAs($creator);
        $createResponse = $this->postJson('/api/events', [
            'title' => 'RSVP Chatroom Test',
            'description' => 'Testing RSVP add to chat',
            'location_name' => 'Test Location',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'starts_at' => now()->addDay()->toIso8601String(),
            'ends_at' => now()->addDay()->addHours(2)->toIso8601String(),
        ]);
        
        $eventId = $createResponse->json('id');
        $event = Event::find($eventId);
        $chatroom = Chatroom::find($event->chatroom_id);

        $this->actingAs($attendee);
        $rsvpResponse = $this->postJson("/api/events/{$eventId}/rsvp", [
            'status' => 'attending'
        ]);
        
        $rsvpResponse->assertStatus(200);
        
        // Refresh chatroom relationship
        $this->assertTrue($chatroom->hasMember($attendee));
    }
    
    public function test_declining_rsvp_removes_user_from_chatroom()
    {
        $creator = User::factory()->create();
        $attendee = User::factory()->create();
        
        $this->actingAs($creator);
        $createResponse = $this->postJson('/api/events', [
            'title' => 'Decline Chatroom Test',
            'description' => 'Testing RSVP remove from chat',
            'location_name' => 'Test Location',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'starts_at' => now()->addDay()->toIso8601String(),
            'ends_at' => now()->addDay()->addHours(2)->toIso8601String(),
        ]);
        
        $eventId = $createResponse->json('id');
        $event = Event::find($eventId);
        $chatroom = Chatroom::find($event->chatroom_id);

        // First attend
        $this->actingAs($attendee);
        $this->postJson("/api/events/{$eventId}/rsvp", ['status' => 'attending']);
        $this->assertTrue($chatroom->hasMember($attendee));
        
        // Then decline
        $this->postJson("/api/events/{$eventId}/rsvp", ['status' => 'declined']);
        
        // Should be removed
        $this->assertFalse($chatroom->hasMember($attendee));
    }
}
