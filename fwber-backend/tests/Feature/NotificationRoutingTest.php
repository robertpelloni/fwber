<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_endpoint_exposes_consistent_message_and_match_routes(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        $user = User::factory()->create();
        $sender = User::factory()->create(['name' => 'Riley']);
        $match = User::factory()->create(['name' => 'Jordan']);

        DB::table('notifications')->insert([
            [
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\NewMessageNotification',
                'notifiable_type' => $user->getMorphClass(),
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'type' => 'message',
                    'title' => 'New Message from '.$sender->name,
                    'body' => 'Encrypted hello',
                    'message' => 'Encrypted hello',
                    'url' => '/messages?user='.$sender->id,
                    'user_id' => $sender->id,
                    'user_name' => $sender->name,
                    'sender_id' => $sender->id,
                    'sender_name' => $sender->name,
                ]),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\NewMatchNotification',
                'notifiable_type' => $user->getMorphClass(),
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'type' => 'match',
                    'title' => 'New Match!',
                    'body' => 'You matched with '.$match->name.'!',
                    'message' => 'You matched with '.$match->name.'!',
                    'url' => '/matches',
                    'user_id' => $match->id,
                    'user_name' => $match->name,
                    'matched_user_id' => $match->id,
                    'matched_user_name' => $match->name,
                ]),
                'read_at' => null,
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ],
        ]);

        $response = $this->actingAs($user)->getJson('/api/notifications');

        $response->assertOk();
        $response->assertJsonPath('unread_count', 2);
        $response->assertJsonFragment([
            'type' => 'message',
            'title' => 'New Message from Riley',
            'body' => 'Encrypted hello',
            'url' => '/messages?user='.$sender->id,
            'user_id' => $sender->id,
            'user_name' => 'Riley',
        ]);
        $response->assertJsonFragment([
            'type' => 'match',
            'title' => 'New Match!',
            'body' => 'You matched with Jordan!',
            'url' => '/matches',
            'user_id' => $match->id,
            'user_name' => 'Jordan',
        ]);
    }
}
