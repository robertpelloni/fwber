<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_endpoint_handles_malformed_stored_payloads(): void
    {
        $user = User::factory()->create();

        DB::table('notifications')->insert([
            [
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\PushMessage',
                'notifiable_type' => $user->getMorphClass(),
                'notifiable_id' => $user->getKey(),
                'data' => '{"title":"Hello","message":"World"}',
                'read_at' => null,
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\PushMessage',
                'notifiable_type' => $user->getMorphClass(),
                'notifiable_id' => $user->getKey(),
                'data' => '{"title":"Broken"',
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($user)->getJson('/api/notifications');

        $response->assertOk();

        $notifications = collect($response->json('notifications'));

        $this->assertGreaterThanOrEqual(2, $response->json('unread_count'));
        $this->assertGreaterThanOrEqual(2, $notifications->count());
        $this->assertTrue($notifications->contains(fn (array $notification) => $notification['title'] === 'Notification'));
        $this->assertTrue($notifications->contains(fn (array $notification) => $notification['title'] === 'Hello' && $notification['body'] === 'World'));
    }
}
