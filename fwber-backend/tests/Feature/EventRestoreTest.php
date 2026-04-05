<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_list_and_rsvp_events(): void
    {
        $user = User::factory()->create();

        $createResponse = $this->actingAs($user)->postJson('/api/events', [
            'title' => 'Community Meetup',
            'description' => 'Meet nearby people.',
            'location_name' => 'Central Park',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'starts_at' => now()->addDay()->toISOString(),
            'ends_at' => now()->addDay()->addHours(2)->toISOString(),
        ]);

        $createResponse->assertStatus(201);
        $eventId = $createResponse->json('id');

        $this->actingAs($user)->getJson('/api/events')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Community Meetup']);

        $this->actingAs($user)->postJson("/api/events/{$eventId}/rsvp", [
            'status' => 'attending',
        ])->assertOk()->assertJsonPath('status', 'attending');
    }

    public function test_user_can_receive_and_accept_event_invitation(): void
    {
        $host = User::factory()->create();
        $guest = User::factory()->create();

        $event = Event::create([
            'title' => 'Invite Only Party',
            'description' => 'Private event.',
            'location_name' => 'Studio Loft',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(3),
            'created_by_user_id' => $host->id,
            'status' => 'upcoming',
        ]);

        $this->actingAs($host)->postJson("/api/events/{$event->id}/invite", [
            'user_id' => $guest->id,
        ])->assertStatus(201);

        $invitation = EventInvitation::firstOrFail();

        $this->actingAs($guest)->getJson('/api/events/invitations')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Invite Only Party']);

        $this->actingAs($guest)->postJson("/api/events/invitations/{$invitation->id}/respond", [
            'status' => 'accepted',
        ])->assertOk();

        $this->assertDatabaseHas('event_attendees', [
            'event_id' => $event->id,
            'user_id' => $guest->id,
            'status' => 'attending',
        ]);
    }
}
