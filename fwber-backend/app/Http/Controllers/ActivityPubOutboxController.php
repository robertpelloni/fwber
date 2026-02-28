<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class ActivityPubOutboxController extends Controller
{
    /**
     * Lists activities published by this user.
     * 
     * @param int $id The local user ID
     */
    public function index($id)
    {
        $user = User::where('id', $id)
            ->whereHas('profile', function($q) {
                $q->where('is_federated', true);
            })->first();

        if (!$user) {
            return response()->json(['error' => 'Outbox not found'], 404);
        }

        // Return standard OrderedCollection of activities
        return response()->json([
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => url("/api/federation/users/{$user->id}/outbox"),
            'type' => 'OrderedCollection',
            'totalItems' => 0, // Placeholder
            'first' => url("/api/federation/users/{$user->id}/outbox?page=true"),
            'last' => url("/api/federation/users/{$user->id}/outbox?min_id=0&page=true")
        ])->header('Content-Type', 'application/activity+json');
    }
}
