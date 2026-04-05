<?php

namespace Tests\Feature;

use App\Events\VideoSignal;
use App\Models\User;
use App\Models\VideoCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class VideoChatRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_initiate_a_video_call_and_send_signaling(): void
    {
        Event::fake([VideoSignal::class]);

        $caller = User::factory()->create();
        $receiver = User::factory()->create();

        $response = $this->actingAs($caller)
            ->postJson('/api/video/initiate', [
                'recipient_id' => $receiver->id,
            ])
            ->assertCreated()
            ->assertJsonPath('caller_id', $caller->id)
            ->assertJsonPath('receiver_id', $receiver->id)
            ->assertJsonPath('status', 'initiated');

        $callId = $response->json('id');

        $this->actingAs($caller)
            ->postJson('/api/video/signal', [
                'recipient_id' => $receiver->id,
                'call_id' => $callId,
                'signal' => [
                    'type' => 'offer',
                    'sdp' => ['type' => 'offer', 'sdp' => 'test-sdp'],
                ],
            ])
            ->assertOk()
            ->assertJson(['status' => 'sent']);

        Event::assertDispatched(VideoSignal::class, function (VideoSignal $event) use ($caller, $receiver, $callId): bool {
            return $event->from_user_id === $caller->id
                && $event->to_user_id === $receiver->id
                && $event->call_id === $callId
                && $event->signal['type'] === 'offer';
        });
    }

    public function test_participant_can_update_status_and_view_call_history(): void
    {
        $caller = User::factory()->create(['name' => 'Caller']);
        $receiver = User::factory()->create(['name' => 'Receiver']);

        $call = VideoCall::query()->create([
            'caller_id' => $caller->id,
            'receiver_id' => $receiver->id,
            'started_at' => now()->subMinutes(5),
            'status' => 'initiated',
        ]);

        $this->actingAs($receiver)
            ->putJson("/api/video/{$call->id}/status", [
                'status' => 'ended',
                'duration' => 180,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'ended')
            ->assertJsonPath('duration', 180);

        $this->assertDatabaseHas('video_calls', [
            'id' => $call->id,
            'status' => 'ended',
            'duration' => 180,
        ]);

        $this->actingAs($caller)
            ->getJson('/api/video/history')
            ->assertOk()
            ->assertJsonPath('data.0.id', $call->id)
            ->assertJsonPath('data.0.caller.name', 'Caller')
            ->assertJsonPath('data.0.receiver.name', 'Receiver');
    }
}
