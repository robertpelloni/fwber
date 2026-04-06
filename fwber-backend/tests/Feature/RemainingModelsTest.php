<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\Group;
use App\Models\GroupModerationEvent;
use App\Models\MatchAction;
use App\Models\User;
use App\Models\UserLocation;
use App\Models\UserPhysicalProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemainingModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_user_location(): void
    {
        $location = UserLocation::factory()->create();

        $this->assertDatabaseHas('user_locations', [
            'id' => $location->id,
            'user_id' => $location->user_id,
        ]);
    }

    public function test_can_create_user_physical_profile(): void
    {
        $profile = UserPhysicalProfile::factory()->create();

        $this->assertDatabaseHas('user_physical_profiles', [
            'id' => $profile->id,
            'user_id' => $profile->user_id,
        ]);
    }

    public function test_can_create_match_action(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $action = MatchAction::create([
            'user_id' => $user->id,
            'target_user_id' => $target->id,
            'action' => 'like',
        ]);

        $this->assertDatabaseHas('match_actions', [
            'id' => $action->id,
            'action' => 'like',
        ]);
    }

    public function test_can_create_device_token(): void
    {
        $user = User::factory()->create();

        $token = DeviceToken::create([
            'user_id' => $user->id,
            'token' => 'test-token-123',
            'type' => 'fcm',
        ]);

        $this->assertDatabaseHas('device_tokens', [
            'id' => $token->id,
            'token' => 'test-token-123',
        ]);
    }

    public function test_can_create_group_moderation_event(): void
    {
        $group = Group::factory()->create();
        $actor = User::factory()->create();
        $target = User::factory()->create();

        $event = GroupModerationEvent::create([
            'group_id' => $group->id,
            'actor_user_id' => $actor->id,
            'target_user_id' => $target->id,
            'action' => 'kick',
            'reason' => 'spam',
        ]);

        $this->assertDatabaseHas('group_moderation_events', [
            'id' => $event->id,
            'action' => 'kick',
        ]);
    }
}
