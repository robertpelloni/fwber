<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BurnerLinkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        
        $token = bin2hex(random_bytes(32));
        
        $burnerLink = \App\Models\BurnerLink::create([
            'creator_id' => $user->id,
            'token' => $token,
            'expires_at' => now()->addHours(24),
        ]);

        return response()->json([
            'token' => $burnerLink->token,
            'expires_at' => $burnerLink->expires_at,
            'url' => url("/burner/join/{$burnerLink->token}")
        ], 201);
    }

    /**
     * Join an ephemeral chat using a burner link token.
     */
    public function join(Request $request, $token)
    {
        $user = auth()->user();

        $burnerLink = \App\Models\BurnerLink::with('chatroom')->where('token', $token)->first();

        if (!$burnerLink) {
            return response()->json(['message' => 'Invalid or expired burner link.'], 404);
        }

        if ($burnerLink->isExpired()) {
            return response()->json(['message' => 'This burner link has expired.'], 403);
        }

        if ($burnerLink->isUsed()) {
            // If the current user is already a part of this chatroom, just return it
            if ($burnerLink->chatroom_id && $burnerLink->chatroom && $burnerLink->chatroom->hasMember($user)) {
                return response()->json([
                    'message' => 'You are already in this chatroom.',
                    'chatroom_id' => $burnerLink->chatroom_id
                ]);
            }
            return response()->json(['message' => 'This burner link has already been used.'], 403);
        }

        if ($burnerLink->creator_id === $user->id) {
            return response()->json(['message' => 'You cannot scan your own burner link.'], 400);
        }

        // Create the ephemeral chatroom
        $chatroom = \App\Models\Chatroom::create([
            'name' => 'Burner Chat',
            'description' => 'A temporary anonymous connection.',
            'type' => 'private',
            'created_by' => $burnerLink->creator_id,
            'is_public' => false,
            'is_active' => true,
            'expires_at' => now()->addHours(24),
            'member_count' => 0,
            'message_count' => 0,
        ]);

        // Add both members
        $creator = \App\Models\User::find($burnerLink->creator_id);
        
        // Wrap in transaction for safety
        \Illuminate\Support\Facades\DB::transaction(function () use ($chatroom, $creator, $user, $burnerLink) {
            $chatroom->addMember($creator);
            $chatroom->addMember($user);

            // Send a system message
            \App\Models\ChatroomMessage::create([
                'chatroom_id' => $chatroom->id,
                'user_id' => $creator->id,
                'content' => "Burner connection established. This chat self-destructs in 24 hours.",
                'type' => 'system',
                'is_edited' => false,
                'is_deleted' => false,
            ]);
            
            $chatroom->update(['message_count' => 1, 'last_activity_at' => now()]);

            // Mark link as used
            $burnerLink->update([
                'scanner_id' => $user->id,
                'chatroom_id' => $chatroom->id,
                'used_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'Burner connection established successfully.',
            'chatroom_id' => $chatroom->id
        ]);
    }
}
