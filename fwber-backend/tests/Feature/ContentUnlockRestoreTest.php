<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use App\Models\UserMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentUnlockRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_unlock_match_insights_with_tokens(): void
    {
        $user = User::factory()->create(['token_balance' => 25]);
        $target = User::factory()->create();

        UserMatch::query()->create([
            'user1_id' => $user->id,
            'user2_id' => $target->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->postJson('/api/content-unlocks', [
                'content_type' => 'match_insights',
                'content_id' => $target->id,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Unlocked successfully.');

        $user->refresh();
        $this->assertSame('15.00', number_format((float) $user->token_balance, 2, '.', ''));
        $this->assertDatabaseHas('content_unlocks', [
            'user_id' => $user->id,
            'content_type' => 'match_insights',
            'content_id' => (string) $target->id,
        ]);
    }

    public function test_user_can_unlock_private_photo_with_tokens(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create(['token_balance' => 80]);
        $photo = Photo::query()->create([
            'user_id' => $owner->id,
            'storage_path' => 'photos/test.jpg',
            'url' => 'https://example.com/test.jpg',
            'is_private' => true,
            'unlock_price' => 25,
            'order' => 0,
        ]);

        $this->actingAs($viewer)
            ->postJson('/api/content-unlocks', [
                'content_type' => 'photo',
                'content_id' => $photo->id,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Photo unlocked successfully.');

        $viewer->refresh();
        $this->assertSame('55.00', number_format((float) $viewer->token_balance, 2, '.', ''));
        $this->assertDatabaseHas('photo_unlocks', [
            'user_id' => $viewer->id,
            'photo_id' => $photo->id,
        ]);
    }

    public function test_match_insights_endpoint_returns_locked_then_unlocked_payload(): void
    {
        $user = User::factory()->create(['token_balance' => 25]);
        $target = User::factory()->create();

        $user->profile()->create(['display_name' => 'User']);
        $target->profile()->create(['display_name' => 'Target']);

        UserMatch::query()->create([
            'user1_id' => $user->id,
            'user2_id' => $target->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->getJson("/api/matches/{$target->id}/insights")
            ->assertOk()
            ->assertJsonPath('data.is_locked', true)
            ->assertJsonPath('data.cost', 10);

        $this->actingAs($user)
            ->postJson("/api/matches/{$target->id}/insights/unlock")
            ->assertOk();

        $this->actingAs($user)
            ->getJson("/api/matches/{$target->id}/insights")
            ->assertOk()
            ->assertJsonPath('data.is_locked', false)
            ->assertJsonStructure([
                'data' => [
                    'total_score',
                    'breakdown',
                    'details',
                ],
            ]);
    }
}
