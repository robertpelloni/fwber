<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventInvitation;
use App\Models\User;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EventInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_invite_another_user_to_event()
    {
        \Illuminate\Support\Facades\Notification::fake();

        $inviter = User::factory()->create();
        $invitee = User::factory()->create();
        $event = Event::factory()->create(['created_by_user_id' => $inviter->id]);

        $response = $this->actingAs($inviter)
            ->postJson("/api/events/{$event->id}/invite", [
                'user_id' => $invitee->id,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('event_invitations', [
            'event_id' => $event->id,
            'inviter_id' => $inviter->id,
            'invitee_id' => $invitee->id,
            'status' => 'pending',
        ]);

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $invitee,
            \App\Notifications\EventInvitationReceived::class
        );
    }

    public function test_user_can_invite_group_to_event()
    {
        \Illuminate\Support\Facades\Notification::fake();

        $inviter = User::factory()->create();
        $event = Event::factory()->create(['created_by_user_id' => $inviter->id]);
        
        $group = Group::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        GroupMember::create(['group_id' => $group->id, 'user_id' => $user1->id, 'role' => 'member']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $user2->id, 'role' => 'member']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $user3->id, 'role' => 'member']);
        // Add inviter to group too (should not be invited)
        GroupMember::create(['group_id' => $group->id, 'user_id' => $inviter->id, 'role' => 'member']);

        $response = $this->actingAs($inviter)
            ->postJson("/api/events/{$event->id}/invite", [
                'group_id' => $group->id,
            ]);

        $response->assertStatus(201)
            ->assertJson(['count' => 3]);

        $this->assertDatabaseHas('event_invitations', ['event_id' => $event->id, 'invitee_id' => $user1->id, 'status' => 'pending']);
        $this->assertDatabaseHas('event_invitations', ['event_id' => $event->id, 'invitee_id' => $user2->id, 'status' => 'pending']);
        $this->assertDatabaseHas('event_invitations', ['event_id' => $event->id, 'invitee_id' => $user3->id, 'status' => 'pending']);
        $this->assertDatabaseMissing('event_invitations', ['event_id' => $event->id, 'invitee_id' => $inviter->id]);

        \Illuminate\Support\Facades\Notification::assertSentTo(
            [$user1, $user2, $user3],
            \App\Notifications\EventInvitationReceived::class
        );
    }

    public function test_user_cannot_invite_already_invited_user()
    {
        $inviter = User::factory()->create();
        $invitee = User::factory()->create();
        $event = Event::factory()->create(['created_by_user_id' => $inviter->id]);

        EventInvitation::create([
            'event_id' => $event->id,
            'inviter_id' => $inviter->id,
            'invitee_id' => $invitee->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($inviter)
            ->postJson("/api/events/{$event->id}/invite", [
                'user_id' => $invitee->id,
            ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'User is already invited.']);
    }

    public function test_user_can_view_pending_invitations()
    {
        $user = User::factory()->create();
        $inviter = User::factory()->create();
        $event = Event::factory()->create(['created_by_user_id' => $inviter->id]);

        EventInvitation::create([
            'event_id' => $event->id,
            'inviter_id' => $inviter->id,
            'invitee_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/events/invitations');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['event_id' => $event->id]);
    }

    public function test_user_can_accept_invitation()
    {
        $user = User::factory()->create();
        $inviter = User::factory()->create();
        $event = Event::factory()->create(['created_by_user_id' => $inviter->id]);

        $invitation = EventInvitation::create([
            'event_id' => $event->id,
            'inviter_id' => $inviter->id,
            'invitee_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/events/invitations/{$invitation->id}/respond", [
                'status' => 'accepted',
            ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('event_invitations', [
            'id' => $invitation->id,
            'status' => 'accepted',
        ]);

        $this->assertDatabaseHas('event_attendees', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'attending',
        ]);
    }

    public function test_user_can_decline_invitation()
    {
        $user = User::factory()->create();
        $inviter = User::factory()->create();
        $event = Event::factory()->create(['created_by_user_id' => $inviter->id]);

        $invitation = EventInvitation::create([
            'event_id' => $event->id,
            'inviter_id' => $inviter->id,
            'invitee_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/events/invitations/{$invitation->id}/respond", [
                'status' => 'declined',
            ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('event_invitations', [
            'id' => $invitation->id,
            'status' => 'declined',
        ]);

        $this->assertDatabaseMissing('event_attendees', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
    }
}
