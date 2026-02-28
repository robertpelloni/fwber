<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\ActivityPubService;

class ActivityPubController extends Controller
{
    /**
     * Resolves the Actor profile via /api/federation/users/{id}
     */
    public function actor($id, ActivityPubService $service)
    {
        $user = User::where('id', $id)
                    ->whereHas('profile', function($q) {
                        $q->where('is_federated', true);
                    })->first();

        if (!$user) {
            return response()->json(['error' => 'Actor not found'], 404);
        }

        $payload = $service->generateActorPayload($user);

        return response()->json($payload)
            ->header('Content-Type', 'application/activity+json');
    }
}
