<?php

namespace App\Http\Controllers;

use App\Models\AudioRoom;
use App\Models\AudioRoomParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AudioRoomController extends Controller
{
    /**
     * List all active audio rooms.
     */
    public function index()
    {
        $rooms = AudioRoom::withCount('participants')
            ->with(['host:id,name,avatar_url,tier'])
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($rooms);
    }

    /**
     * Create a new audio room.
     */
    public function store(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
            'name' => 'nullable|string|max:100',
        ]);

        $room = DB::transaction(function () use ($request) {
            $room = AudioRoom::create([
                'name' => $request->name,
                'topic' => $request->topic,
                'host_id' => Auth::id(),
                'status' => 'active',
            ]);

            // Host joins automatically as speaker, unmuted (managed by frontend state, but default here)
            AudioRoomParticipant::create([
                'audio_room_id' => $room->id,
                'user_id' => Auth::id(),
                'role' => 'speaker',
                'is_muted' => false,
            ]);

            return $room;
        });

        // Eager load for the response
        $room->load(['host', 'participants.user']);
        $room->participants_count = 1;

        return response()->json($room, 201);
    }
    
    /**
     * Get specific room details.
     */
    public function show($id)
    {
        $room = AudioRoom::with(['host', 'participants.user'])->findOrFail($id);
        
        return response()->json($room);
    }

    /**
     * Join an existing audio room.
     */
    public function join(Request $request, $id)
    {
        $room = AudioRoom::where('status', 'active')->findOrFail($id);
        $userId = Auth::id();

        $participant = AudioRoomParticipant::firstOrCreate(
            ['audio_room_id' => $room->id, 'user_id' => $userId],
            ['role' => 'listener', 'is_muted' => true]
        );

        $participant->load('user');

        // Optional: Broadcast event that user joined
        // broadcast(new AudioRoomParticipantJoined($room, $participant))->toOthers();

        return response()->json([
            'message' => 'Joined room successfully',
            'room' => $room->load(['host']),
            'participant' => $participant,
            'participants' => $room->participants()->with('user')->get()
        ]);
    }

    /**
     * Leave an audio room.
     */
    public function leave(Request $request, $id)
    {
        $room = AudioRoom::findOrFail($id);
        $userId = Auth::id();

        AudioRoomParticipant::where('audio_room_id', $room->id)
            ->where('user_id', $userId)
            ->delete();

        // Optional: Broadcast event that user left
        // broadcast(new AudioRoomParticipantLeft($room, $userId))->toOthers();

        // If host leaves, end the room
        if ($room->host_id === $userId) {
            $room->update(['status' => 'ended']);
            // broadcast(new AudioRoomEnded($room))->toOthers();
        }

        return response()->json(['message' => 'Left room successfully']);
    }

    /**
     * Handle WebRTC signaling exchange (Offers, Answers, ICE candidates).
     * This relies heavily on Laravel Reverb / Pusher broadcast channels.
     */
    public function signal(Request $request, $id)
    {
        $request->validate([
            'target_user_id' => 'required|exists:users,id',
            'type' => 'required|string|in:offer,answer,ice-candidate',
            'payload' => 'required',
        ]);

        $room = AudioRoom::findOrFail($id);
        $senderId = Auth::id();
        $targetId = $request->target_user_id;

        // In a real implementation this fires a constrained event over Echo
        // We broadcast to a private channel like: private-user.{targetId} or private-audio-room.{roomId}
        //
        // Example:
        // broadcast(new WebRTCSignalReceived(
        //     $room->id, 
        //     $senderId, 
        //     $targetId, 
        //     $request->type, 
        //     $request->payload
        // ))->toOthers();

        return response()->json(['message' => 'Signal dispatched']);
    }
}
