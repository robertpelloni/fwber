<?php

namespace App\Http\Controllers;

use App\Events\VideoSignal;
use App\Http\Requests\VideoChat\InitiateVideoChatRequest;
use App\Http\Requests\VideoChat\SignalVideoChatRequest;
use App\Http\Requests\VideoChat\UpdateVideoCallStatusRequest;
use App\Models\VideoCall;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoChatController extends Controller
{
    /**
     * Persist a new call log before WebRTC signaling proceeds.
     *
     * Storing the call up front gives the frontend a stable call id for later
     * status updates, missed-call handling, and call-history rendering.
     */
    public function initiate(InitiateVideoChatRequest $request): JsonResponse
    {
        $call = VideoCall::query()->create([
            'caller_id' => $request->user()->id,
            'receiver_id' => (int) $request->integer('recipient_id'),
            'started_at' => now(),
            'status' => 'initiated',
        ]);

        return response()->json($call, 201);
    }

    /**
     * Relay SDP / ICE messages over Reverb so the existing frontend video modal
     * can complete its peer-connection handshake.
     */
    public function signal(SignalVideoChatRequest $request): JsonResponse
    {
        VideoSignal::dispatch(
            $request->user()->id,
            (int) $request->integer('recipient_id'),
            $request->validated('signal'),
            $request->validated('call_id')
        );

        return response()->json(['status' => 'sent']);
    }

    public function updateStatus(UpdateVideoCallStatusRequest $request, int $id): JsonResponse
    {
        $call = VideoCall::query()->findOrFail($id);
        $userId = $request->user()->id;

        if ($call->caller_id !== $userId && $call->receiver_id !== $userId) {
            return response()->json([
                'message' => 'Unauthorized to update this video call.',
            ], 403);
        }

        $payload = [
            'status' => $request->validated('status'),
        ];

        if ($payload['status'] === 'connected' && ! $call->started_at) {
            $payload['started_at'] = now();
        }

        if (in_array($payload['status'], ['ended', 'missed', 'rejected'], true)) {
            $payload['ended_at'] = now();
            $payload['duration'] = $request->integer('duration') ?: ($call->started_at ? now()->diffInSeconds($call->started_at) : null);
        }

        $call->update($payload);

        return response()->json($call->fresh());
    }

    public function history(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $calls = VideoCall::query()
            ->with(['caller:id,name,avatar_url', 'receiver:id,name,avatar_url'])
            ->where(function ($query) use ($userId): void {
                $query->where('caller_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->latest()
            ->paginate(20);

        return response()->json($calls);
    }
}
