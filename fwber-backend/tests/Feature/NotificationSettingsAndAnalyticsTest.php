<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSettingsAndAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_preferences_can_be_updated_via_type_route(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/notification-preferences/new_message', [
            'push' => false,
            'database' => true,
            'mail' => true,
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'type' => 'new_message',
            'push' => false,
            'database' => true,
            'mail' => true,
        ]);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'type' => 'new_message',
            'push' => false,
            'database' => true,
            'mail' => true,
        ]);
    }

    public function test_public_analytics_events_endpoint_accepts_page_view_payloads(): void
    {
        $response = $this->postJson('/api/analytics/events', [
            'session_id' => 'session-test-123',
            'events' => [
                [
                    'event_name' => 'page_view',
                    'payload' => ['path' => '/login'],
                    'url' => 'https://www.fwber.me/login',
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
        ]);
    }
}
