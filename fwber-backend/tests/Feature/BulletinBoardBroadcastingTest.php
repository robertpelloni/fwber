<?php

namespace Tests\Feature;

use App\Events\BulletinBoardActivity;
use App\Events\BulletinMessageCreated;
use App\Models\BulletinBoard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BulletinBoardBroadcastingTest extends TestCase
{
    use RefreshDatabase;

    public function test_posting_message_broadcasts_event()
    {
        Event::fake([
            BulletinMessageCreated::class,
            BulletinBoardActivity::class,
        ]);

        $user = User::factory()->create();
        $board = BulletinBoard::factory()->create([
            'center_lat' => 40.7128,
            'center_lng' => -74.0060,
            'radius_meters' => 2000,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/bulletin-boards/{$board->id}/messages", [
                'content' => 'Hello World',
                'lat' => 40.7128,
                'lng' => -74.0060,
            ]);

        $response->assertStatus(201);

        Event::assertDispatched(BulletinMessageCreated::class, function ($event) use ($user) {
            return $event->message->content === 'Hello World' && $event->message->user_id === $user->id;
        });

        Event::assertDispatched(BulletinBoardActivity::class, function ($event) use ($board) {
            return $event->board->id === $board->id;
        });
    }
}
