<?php

namespace Tests\Feature;

use App\Models\Friend;
use App\Models\RelationshipLink;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelationshipLinkFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function createAcceptedFriendship(User $first, User $second): void
    {
        Friend::factory()->create(['user_id' => $first->id, 'friend_id' => $second->id, 'status' => 'accepted']);
        Friend::factory()->create(['user_id' => $second->id, 'friend_id' => $first->id, 'status' => 'accepted']);
    }

    public function test_user_can_propose_relationship_link_to_friend(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $this->createAcceptedFriendship($user, $friend);

        $response = $this->actingAs($user)->postJson('/api/relationship-links', [
            'related_user_id' => $friend->id,
            'relationship_type' => 'partner',
            'visibility' => 'friends',
            'note' => 'Keeping this within our trusted circle.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('link.relationship_type', 'partner')
            ->assertJsonPath('link.is_confirmed', false);

        $this->assertDatabaseHas('relationship_links', [
            'user_id' => $user->id,
            'related_user_id' => $friend->id,
            'relationship_type' => 'partner',
            'visibility' => 'friends',
        ]);
    }

    public function test_user_cannot_propose_relationship_link_to_non_friend(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/relationship-links', [
            'related_user_id' => $other->id,
            'relationship_type' => 'dating',
            'visibility' => 'public',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('relationship_links', 0);
    }

    public function test_related_user_can_accept_relationship_link_request(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $this->createAcceptedFriendship($user, $friend);

        $link = RelationshipLink::create([
            'user_id' => $user->id,
            'related_user_id' => $friend->id,
            'relationship_type' => 'dating',
            'visibility' => 'friends',
            'requested_at' => now(),
        ]);

        $response = $this->actingAs($friend)->postJson("/api/relationship-links/{$link->id}/respond", [
            'status' => 'accepted',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('link.is_confirmed', true);

        $this->assertDatabaseHas('relationship_links', [
            'id' => $link->id,
        ]);
        $this->assertNotNull($link->fresh()->confirmed_at);
    }

    public function test_friends_visible_link_appears_on_friend_profile_view_only(): void
    {
        $owner = User::factory()->create();
        $partner = User::factory()->create();
        $viewer = User::factory()->create();
        $stranger = User::factory()->create();

        foreach ([$owner, $partner, $viewer, $stranger] as $user) {
            UserProfile::factory()->create(['user_id' => $user->id]);
        }

        $this->createAcceptedFriendship($owner, $partner);
        $this->createAcceptedFriendship($owner, $viewer);

        RelationshipLink::create([
            'user_id' => $owner->id,
            'related_user_id' => $partner->id,
            'relationship_type' => 'partner',
            'visibility' => 'friends',
            'requested_at' => now(),
            'confirmed_at' => now(),
        ]);

        $friendView = $this->actingAs($viewer)->getJson("/api/users/{$owner->id}");
        $friendView->assertStatus(200)
            ->assertJsonPath('data.profile.relationship_links.0.relationship_type', 'partner');

        $strangerView = $this->actingAs($stranger)->getJson("/api/users/{$owner->id}");
        $strangerView->assertStatus(200)
            ->assertJsonPath('data.profile.relationship_links', []);
    }

    public function test_removing_friend_also_removes_relationship_link(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $this->createAcceptedFriendship($user, $friend);

        $link = RelationshipLink::create([
            'user_id' => $user->id,
            'related_user_id' => $friend->id,
            'relationship_type' => 'spouse',
            'visibility' => 'public',
            'requested_at' => now(),
            'confirmed_at' => now(),
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/friends/{$friend->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('relationship_links', ['id' => $link->id]);
    }
}
