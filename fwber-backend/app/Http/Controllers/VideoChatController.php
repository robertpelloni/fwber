<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MercurePublisher;
use Illuminate\Support\Facades\Auth;

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
        ]);

        $sender = Auth::user();
        $recipientId = $request->input('recipient_id');
        $signal = $request->input('signal');

        // Publish to recipient's private topic
        $this->mercure->publish(
            "https://fwber.me/user/{$recipientId}",
            [
                'type' => 'video_signal',
                'from_user_id' => $sender->id,
                'signal' => $signal,
                'timestamp' => now()->toIso8601String(),
            ]
        );

        return response()->json(['status' => 'sent']);
    }
}
