<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MercurePublisher;
use Illuminate\Support\Facades\Auth;
use App\Models\VideoCall;

class VideoChatController extends Controller
{
    protected $mercure;

    public function __construct(MercurePublisher $mercure)
    {
        $this->mercure = $mercure;
    }

    /**
     * Handle WebRTC signaling messages
     */
    public function signal(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|integer',
            'signal' => 'required|array', // type (offer/answer/candidate/bye), sdp/candidate data
            'call_id' => 'nullable|integer|exists:video_calls,id',
        ]);

        $sender = Auth::user();
        $recipientId = $request->input('recipient_id');
        $signal = $request->input('signal');
        $callId = $request->input('call_id');

        // Publish to recipient's private topic
        $this->mercure->publish(
            "https://fwber.me/user/{$recipientId}",
            [
                'type' => 'video_signal',
                'from_user_id' => $sender->id,
                'signal' => $signal,
                'call_id' => $callId,
                'timestamp' => now()->toIso8601String(),
            ]
        );

        return response()->json(['status' => 'sent']);
    }

    /**
     * Initiate a new video call log
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|integer|exists:users,id',
        ]);

        $call = VideoCall::create([
            'caller_id' => Auth::id(),
            'receiver_id' => $request->recipient_id,
            'started_at' => now(),
            'status' => 'initiated',
        ]);

        return response()->json($call);
    }

    /**
     * Update call status (connected, rejected, ended, etc.)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:connected,missed,rejected,ended',
            'duration' => 'nullable|integer',
        ]);

        $call = VideoCall::findOrFail($id);

        // Ensure user is participant
        if ($call->caller_id !== Auth::id() && $call->receiver_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $updateData = ['status' => $request->status];

        if ($request->status === 'ended') {
            $updateData['ended_at'] = now();
            if ($request->has('duration')) {
                $updateData['duration'] = $request->duration;
            } elseif ($call->started_at) {
                $updateData['duration'] = now()->diffInSeconds($call->started_at);
            }
        }

        $call->update($updateData);

        return response()->json($call);
    }

    /**
     * Get call history for the authenticated user
     */
    public function history(Request $request)
    {
        $userId = Auth::id();
        
        $calls = VideoCall::with(['caller:id,name,avatar_url', 'receiver:id,name,avatar_url'])
            ->where('caller_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($calls);
    }
}
