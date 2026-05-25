<?php

namespace Tests\Feature;

use App\Events\VideoSignal;
use App\Models\User;
use App\Models\VideoCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class VideoChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_initiate_video_call()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Must be matched to call
        \App\Models\MatchAction::create([
            'user_id' => $user1->id,
            'target_user_id' => $user2->id,
            'action' => 'like',
        ]);
        \App\Models\MatchAction::create([
            'user_id' => $user2->id,
            'target_user_id' => $user1->id,
            'action' => 'like',
        ]);

        $response = $this->actingAs($user1)->postJson('/api/video/initiate', [
            'recipient_id' => $user2->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['id', 'status', 'caller_id', 'receiver_id']);

        $this->assertDatabaseHas('video_calls', [
            'caller_id' => $user1->id,
            'receiver_id' => $user2->id,
            'status' => 'initiated',
        ]);
    }

    public function test_user_can_send_video_signal()
    {
        Event::fake();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $call = VideoCall::create([
            'caller_id' => $user1->id,
            'receiver_id' => $user2->id,
            'status' => 'initiated',
        ]);

        $response = $this->actingAs($user1)->postJson('/api/video/signal', [
            'recipient_id' => $user2->id,
            'signal' => ['type' => 'offer', 'sdp' => 'v=0...'],
            'call_id' => $call->id,
        ]);

        $response->assertStatus(200);

        Event::assertDispatched(VideoSignal::class, function ($event) use ($user1, $user2, $call) {
            return $event->from_user_id === $user1->id &&
                   $event->to_user_id === $user2->id &&
                   $event->call_id === $call->id;
        });
    }

    public function test_user_can_update_call_status()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $call = VideoCall::create([
            'caller_id' => $user1->id,
            'receiver_id' => $user2->id,
            'status' => 'initiated',
        ]);

        $response = $this->actingAs($user2)->putJson("/api/video/{$call->id}/status", [
            'status' => 'connected',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('video_calls', [
            'id' => $call->id,
            'status' => 'connected',
        ]);
    }

    public function test_user_can_fetch_call_history()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        VideoCall::create([
            'caller_id' => $user->id,
            'receiver_id' => $other->id,
            'status' => 'ended',
        ]);

        $response = $this->actingAs($user)->getJson('/api/video/history');

        $response->assertStatus(200);
    }
}
