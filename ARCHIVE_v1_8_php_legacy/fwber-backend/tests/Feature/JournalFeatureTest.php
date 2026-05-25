<?php

namespace Tests\Feature;

use App\Models\Friend;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Journal;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_circle_visible_field_note(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);
        $group = Group::factory()->create(['created_by_user_id' => $user->id]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'member',
            'joined_at' => now(),
            'is_active' => true,
            'is_banned' => false,
        ]);

        $response = $this->actingAs($user)->postJson('/api/journals', [
            'title' => 'Warehouse notes',
            'content' => 'Great crowd energy tonight.',
            'visibility' => 'circle',
            'circle_group_id' => $group->id,
            'tags' => ['music', 'warehouse'],
            'mood_emoji' => '🔥',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('journal.visibility', 'circle')
            ->assertJsonPath('journal.circle_group.id', $group->id);

        $this->assertDatabaseHas('journals', [
            'user_id' => $user->id,
            'visibility' => 'circle',
            'circle_group_id' => $group->id,
        ]);
    }

    public function test_friend_can_see_friends_visible_journal_on_public_profile(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $owner->id]);
        UserProfile::factory()->create(['user_id' => $viewer->id]);

        Friend::factory()->create(['user_id' => $owner->id, 'friend_id' => $viewer->id, 'status' => 'accepted']);
        Friend::factory()->create(['user_id' => $viewer->id, 'friend_id' => $owner->id, 'status' => 'accepted']);

        Journal::create([
            'user_id' => $owner->id,
            'title' => 'Quiet coffee date',
            'content' => 'Saving this for friends who know the story.',
            'visibility' => 'friends',
        ]);

        $response = $this->actingAs($viewer)->getJson("/api/users/{$owner->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.profile.journals.0.visibility', 'friends')
            ->assertJsonPath('data.profile.journals.0.title', 'Quiet coffee date');
    }

    public function test_non_friend_cannot_see_friends_visible_journal_on_public_profile(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $owner->id]);
        UserProfile::factory()->create(['user_id' => $viewer->id]);

        Journal::create([
            'user_id' => $owner->id,
            'title' => 'Private orbit',
            'content' => 'Only friends should see this.',
            'visibility' => 'friends',
        ]);

        $response = $this->actingAs($viewer)->getJson("/api/users/{$owner->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.profile.journals', []);
    }

    public function test_circle_member_can_see_circle_journal_on_public_profile(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $owner->id]);
        UserProfile::factory()->create(['user_id' => $viewer->id]);

        $group = Group::factory()->create(['created_by_user_id' => $owner->id]);

        foreach ([$owner, $viewer] as $memberUser) {
            GroupMember::create([
                'group_id' => $group->id,
                'user_id' => $memberUser->id,
                'role' => 'member',
                'joined_at' => now(),
                'is_active' => true,
                'is_banned' => false,
            ]);
        }

        Journal::create([
            'user_id' => $owner->id,
            'title' => 'Circle check-in',
            'content' => 'For the trusted group only.',
            'visibility' => 'circle',
            'circle_group_id' => $group->id,
        ]);

        $response = $this->actingAs($viewer)->getJson("/api/users/{$owner->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.profile.journals.0.circle_group.id', $group->id)
            ->assertJsonPath('data.profile.journals.0.visibility', 'circle');
    }

    public function test_user_can_save_default_journal_visibility_settings(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);
        $group = Group::factory()->create(['created_by_user_id' => $user->id]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'member',
            'joined_at' => now(),
            'is_active' => true,
            'is_banned' => false,
        ]);

        $response = $this->actingAs($user)->putJson('/api/settings/privacy/journals', [
            'default_visibility' => 'circle',
            'circle_group_id' => $group->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.default_visibility', 'circle')
            ->assertJsonPath('data.circle_group_id', $group->id);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'journal_visibility_default' => 'circle',
            'journal_circle_group_id' => $group->id,
        ]);
    }
}
