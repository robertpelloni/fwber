<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupMessage;
use App\Models\User;
use App\Models\UserProfile;
use Tests\Traits\RefreshDatabaseSilently;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupFeatureTest extends TestCase
{
    use RefreshDatabaseSilently;

    private User $user;
    private User $user2;
    private string $token;
    private string $token2;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');

        $this->user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $this->user->id,
            'display_name' => 'User One',
            'date_of_birth' => now()->subYears(25),
            'gender' => 'male',
            'bio' => 'Test bio',
        ]);

        $this->user2 = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $this->user2->id,
            'display_name' => 'User Two',
            'date_of_birth' => now()->subYears(26),
            'gender' => 'female',
            'bio' => 'Test bio 2',
        ]);

        $this->token = ApiToken::generateForUser($this->user, 'test');
        $this->token2 = ApiToken::generateForUser($this->user2, 'test');
    }

    #[Test]
    public function can_create_group(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/groups', [
                'name' => 'Test Group',
                'description' => 'A test group',
                'visibility' => 'public',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'group' => ['id', 'name', 'description', 'visibility', 'creator_id']
        ]);

        $this->assertDatabaseHas('groups', [
            'name' => 'Test Group',
            'creator_id' => $this->user->id,
        ]);

        $this->assertDatabaseHas('group_members', [
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function can_list_user_groups(): void
    {
        $group = Group::create([
            'name' => 'My Group',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/groups');

        $response->assertOk();
        $response->assertJsonCount(1, 'groups');
        $response->assertJsonPath('groups.0.name', 'My Group');
    }

    #[Test]
    public function can_join_public_group(): void
    {
        $group = Group::create([
            'name' => 'Public Group',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->postJson("/api/groups/{$group->id}/join");

        $response->assertOk();

        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $this->user2->id,
            'role' => 'member',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function cannot_join_private_group(): void
    {
        $group = Group::create([
            'name' => 'Private Group',
            'creator_id' => $this->user->id,
            'visibility' => 'private',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->postJson("/api/groups/{$group->id}/join");

        $response->assertStatus(403);
    }

    #[Test]
    public function member_can_send_message_to_group(): void
    {
        $group = Group::create([
            'name' => 'Chat Group',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/groups/{$group->id}/messages", [
                'content' => 'Hello group!',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message.content', 'Hello group!');

        $this->assertDatabaseHas('group_messages', [
            'group_id' => $group->id,
            'sender_id' => $this->user->id,
            'content' => 'Hello group!',
        ]);
    }

    #[Test]
    public function non_member_cannot_send_message(): void
    {
        $group = Group::create([
            'name' => 'Exclusive Group',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->postJson("/api/groups/{$group->id}/messages", [
                'content' => 'Hello!',
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function can_send_media_message_to_group(): void
    {
        $group = Group::create([
            'name' => 'Media Group',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $imageFile = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/groups/{$group->id}/messages", [
                'content' => 'Check this out!',
                'message_type' => 'image',
                'media' => $imageFile,
            ]);

        $response->assertStatus(201);

        $message = GroupMessage::latest()->first();
        $this->assertEquals('image', $message->message_type);
        $this->assertNotNull($message->media_url);
    }

    #[Test]
    public function can_mark_group_message_as_read(): void
    {
        $group = Group::create([
            'name' => 'Read Group',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user2->id,
            'role' => 'member',
            'is_active' => true,
        ]);

        $message = GroupMessage::create([
            'group_id' => $group->id,
            'sender_id' => $this->user->id,
            'content' => 'Test message',
            'message_type' => 'text',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->postJson("/api/group-messages/{$message->id}/read");

        $response->assertOk();

        $this->assertDatabaseHas('group_message_reads', [
            'group_message_id' => $message->id,
            'user_id' => $this->user2->id,
        ]);
    }

    #[Test]
    public function can_discover_public_groups(): void
    {
        Group::create([
            'name' => 'Public Group 1',
            'description' => 'First public group',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
            'is_active' => true,
        ]);

        Group::create([
            'name' => 'Public Group 2',
            'description' => 'Second public group',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
            'is_active' => true,
        ]);

        Group::create([
            'name' => 'Private Group',
            'creator_id' => $this->user->id,
            'visibility' => 'private',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->getJson('/api/groups/discover');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    #[Test]
    public function can_search_groups_by_name(): void
    {
        Group::create([
            'name' => 'Laravel Developers',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
            'is_active' => true,
        ]);

        Group::create([
            'name' => 'React Developers',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->getJson('/api/groups/discover?search=Laravel');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Laravel Developers', $response->json('data.0.name'));
    }

    #[Test]
    public function owner_can_update_group(): void
    {
        $group = Group::create([
            'name' => 'Original Name',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/groups/{$group->id}", [
                'name' => 'Updated Name',
                'description' => 'Updated description',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);
    }

    #[Test]
    public function non_admin_cannot_update_group(): void
    {
        $group = Group::create([
            'name' => 'Test Group',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user2->id,
            'role' => 'member',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->putJson("/api/groups/{$group->id}", [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function cannot_join_same_group_twice(): void
    {
        $group = Group::create([
            'name' => 'Dup Group',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
        ]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        // First join succeeds
        $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->postJson("/api/groups/{$group->id}/join")
            ->assertOk();

        // Second join attempt
        $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->postJson("/api/groups/{$group->id}/join")
            ->assertStatus(400);
    }

    #[Test]
    public function owner_cannot_leave_group(): void
    {
        $group = Group::create([
            'name' => 'Leave Test',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
        ]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/groups/{$group->id}/leave")
            ->assertStatus(400);
    }

    #[Test]
    public function member_can_leave_group(): void
    {
        $group = Group::create([
            'name' => 'Leave OK',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
        ]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user2->id,
            'role' => 'member',
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->postJson("/api/groups/{$group->id}/leave")
            ->assertOk();

        $member = GroupMember::where('group_id', $group->id)->where('user_id', $this->user2->id)->first();
        $this->assertNotNull($member);
        $this->assertFalse((bool)$member->is_active);
        $this->assertNotNull($member->left_at);
    }

    #[Test]
    public function cannot_join_full_group(): void
    {
        $group = Group::create([
            'name' => 'Full Group',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
            'max_members' => 2,
        ]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        // Second member joins
        $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->postJson("/api/groups/{$group->id}/join")
            ->assertOk();

        // Third user attempts to join
        $user3 = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user3->id,
            'display_name' => 'User Three',
            'date_of_birth' => now()->subYears(27),
            'gender' => 'other',
        ]);
        $token3 = ApiToken::generateForUser($user3, 'test');

        $this->withHeader('Authorization', "Bearer {$token3}")
            ->postJson("/api/groups/{$group->id}/join")
            ->assertStatus(400);
    }

    #[Test]
    public function unread_count_across_groups(): void
    {
        $group = Group::create([
            'name' => 'Unread Group',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
        ]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user2->id,
            'role' => 'member',
            'is_active' => true,
        ]);

        // Two messages from user1
        $m1 = GroupMessage::create([
            'group_id' => $group->id,
            'sender_id' => $this->user->id,
            'content' => 'm1',
            'message_type' => 'text',
            'sent_at' => now(),
        ]);
        $m2 = GroupMessage::create([
            'group_id' => $group->id,
            'sender_id' => $this->user->id,
            'content' => 'm2',
            'message_type' => 'text',
            'sent_at' => now(),
        ]);

        $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->getJson('/api/group-messages/unread-count')
            ->assertOk()
            ->assertJson(['unread_count' => 2]);

        // Mark one as read
        $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->postJson("/api/group-messages/{$m1->id}/read")
            ->assertOk();

        $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->getJson('/api/group-messages/unread-count')
            ->assertOk()
            ->assertJson(['unread_count' => 1]);

        // User2 sends a message; shouldn't count for themselves
        GroupMessage::create([
            'group_id' => $group->id,
            'sender_id' => $this->user2->id,
            'content' => 'm3',
            'message_type' => 'text',
            'sent_at' => now(),
        ]);

        $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->getJson('/api/group-messages/unread-count')
            ->assertOk()
            ->assertJson(['unread_count' => 1]);
    }

    #[Test]
    public function private_group_hidden_from_non_member_show(): void
    {
        $group = Group::create([
            'name' => 'Hidden Private',
            'creator_id' => $this->user->id,
            'visibility' => 'private',
        ]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->getJson("/api/groups/{$group->id}")
            ->assertStatus(404);
    }

    #[Test]
    public function private_group_visible_to_member_show(): void
    {
        $group = Group::create([
            'name' => 'Private OK',
            'creator_id' => $this->user->id,
            'visibility' => 'private',
        ]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user2->id,
            'role' => 'member',
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->getJson("/api/groups/{$group->id}")
            ->assertOk()
            ->assertJsonPath('is_member', true);
    }

    #[Test]
    public function admin_can_update_group(): void
    {
        $group = Group::create([
            'name' => 'Admin Update',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
        ]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user2->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->putJson("/api/groups/{$group->id}", [
                'name' => 'Admin Updated',
            ])
            ->assertOk();

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'name' => 'Admin Updated',
        ]);
    }

    #[Test]
    public function owner_can_delete_group(): void
    {
        $group = Group::create([
            'name' => 'To Delete',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
        ]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/groups/{$group->id}")
            ->assertOk();

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'is_active' => false,
        ]);
    }

    #[Test]
    public function non_owner_cannot_delete_group(): void
    {
        $group = Group::create([
            'name' => 'No Delete',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
        ]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user2->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->deleteJson("/api/groups/{$group->id}")
            ->assertStatus(403);
    }

    #[Test]
    public function non_member_cannot_list_group_messages(): void
    {
        $group = Group::create([
            'name' => 'No List',
            'creator_id' => $this->user->id,
            'visibility' => 'public',
        ]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->getJson("/api/groups/{$group->id}/messages")
            ->assertStatus(403);
    }
}
