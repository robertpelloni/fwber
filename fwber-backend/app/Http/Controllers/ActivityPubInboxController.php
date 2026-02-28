<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ActivityPubInboxController extends Controller
{
    /**
     * Accepts POST requests from other federated servers.
     * 
     * @param int $id The local user ID receiving the activity.
     */
    public function handle(Request $request, $id)
    {
        $user = User::where('id', $id)
            ->whereHas('profile', function($q) {
                $q->where('is_federated', true);
            })->first();

        if (!$user) {
            return response()->json(['error' => 'Inbox not found'], 404);
        }

        $payload = $request->json()->all();

        // Standard ActivityPub routing
        $type = $payload['type'] ?? 'Unknown';

        Log::info("ActivityPub INBOX received type: {$type} for User {$user->id}");

        // In a full implementation, we would dispatch Jobs based on type:
        // - Follow -> Create localized Follower record
        // - Undo -> Remove Follower record
        // - Create (Note) -> Save foreign post to local timeline

        return response()->json(['status' => 'received'], 202); // 202 Accepted
    }
}
