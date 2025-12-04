<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_preferences_with_defaults()
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/notification-preferences');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['type', 'label', 'mail', 'push', 'database']
            ]);
        
        // Check default values
        $newMatch = collect($response->json())->firstWhere('type', 'new_match');
        $this->assertTrue($newMatch['mail']);
        $this->assertTrue($newMatch['push']);
    }

    public function test_user_can_update_preference()
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/notification-preferences/new_match', [
            'mail' => false,
            'push' => true,
            'database' => false,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'type' => 'new_match',
            'mail' => false,
            'push' => true,
            'database' => false,
        ]);
    }

    public function test_update_validates_input()
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/notification-preferences/new_match', [
            'mail' => 'invalid',
        ]);

        $response->assertStatus(422);
    }

    public function test_update_validates_type()
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/notification-preferences/invalid_type', [
            'mail' => false,
        ]);

        $response->assertStatus(400);
    }

    public function test_notification_respects_preferences()
    {
        $user = \App\Models\User::factory()->create();
        $matchedUser = \App\Models\User::factory()->create();

        // Disable mail for new matches
        $user->notificationPreferences()->create([
            'type' => 'new_match',
            'mail' => false,
            'push' => true,
            'database' => true,
        ]);

        $notification = new \App\Notifications\NewMatchNotification($matchedUser);
        $channels = $notification->via($user);

        $this->assertNotContains('mail', $channels);
        $this->assertContains('database', $channels);
        $this->assertContains(\NotificationChannels\WebPush\WebPushChannel::class, $channels);
    }
}
