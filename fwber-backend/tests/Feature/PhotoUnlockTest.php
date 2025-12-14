<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use App\Notifications\PhotoUnlockedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PhotoUnlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_unlock_private_photo_with_sufficient_tokens()
    {
        Notification::fake();

        $owner = User::factory()->create();
        $photo = Photo::factory()->create([
            'user_id' => $owner->id,
            'is_private' => true,
        ]);

        $unlocker = User::factory()->create([
            'token_balance' => 100,
        ]);

        $response = $this->actingAs($unlocker)
            ->postJson("/api/photos/{$photo->id}/unlock");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('photo_unlocks', [
            'user_id' => $unlocker->id,
            'photo_id' => $photo->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $unlocker->id,
            'token_balance' => 50, // 100 - 50
        ]);

        Notification::assertSentTo(
            [$owner], PhotoUnlockedNotification::class
        );
    }

    public function test_user_cannot_unlock_with_insufficient_tokens()
    {
        $owner = User::factory()->create();
        $photo = Photo::factory()->create([
            'user_id' => $owner->id,
            'is_private' => true,
        ]);

        $unlocker = User::factory()->create([
            'token_balance' => 10,
        ]);

        $response = $this->actingAs($unlocker)
            ->postJson("/api/photos/{$photo->id}/unlock");

        $response->assertStatus(402);
        
        $this->assertDatabaseMissing('photo_unlocks', [
            'user_id' => $unlocker->id,
            'photo_id' => $photo->id,
        ]);
    }

    public function test_owner_cannot_unlock_own_photo()
    {
        $owner = User::factory()->create([
            'token_balance' => 100,
        ]);
        $photo = Photo::factory()->create([
            'user_id' => $owner->id,
            'is_private' => true,
        ]);

        $response = $this->actingAs($owner)
            ->postJson("/api/photos/{$photo->id}/unlock");

        $response->assertStatus(200)
            ->assertJson(['message' => 'You own this photo']);

        $this->assertDatabaseMissing('photo_unlocks', [
            'user_id' => $owner->id,
            'photo_id' => $photo->id,
        ]);

        // Balance should remain same
        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
            'token_balance' => 100,
        ]);
    }
}
