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

        $activity = $request->json()->all();
        $type = $activity['type'] ?? 'Unknown';

        Log::info("ActivityPub: Received {$type} for user {$user->id}", $activity);

        switch ($type) {
            case 'Follow':
                return $this->handleFollow($user, $activity);
            
            case 'Undo':
                if (isset($activity['object']['type']) && $activity['object']['type'] === 'Follow') {
                    return $this->handleUnfollow($user, $activity);
                }
                break;

            case 'Create':
                // Handle incoming posts from followed users
                break;
        }

        return response()->json(['status' => 'received'], 202);
    }

    /**
     * Process an incoming Follow activity.
     */
    protected function handleFollow(User $user, array $activity)
    {
        $actorUri = $activity['actor'] ?? null;
        if (!$actorUri) return response()->json(['error' => 'No actor provided'], 400);

        $parsed = parse_url($actorUri);
        $domain = $parsed['host'] ?? null;
        $pathParts = explode('/', trim($parsed['path'] ?? '', '/'));
        $username = end($pathParts);

        \App\Models\Follower::updateOrCreate(
            ['user_id' => $user->id, 'actor_uri' => $actorUri],
            [
                'username' => $username,
                'domain' => $domain,
                'status' => 'accepted'
            ]
        );

        // Notify the user
        $user->notify(new \App\Notifications\FederatedFollowNotification($username, $domain));

        return response()->json(['status' => 'follow_processed'], 202);
    }

    /**
     * Process an incoming Undo Follow activity.
     */
    protected function handleUnfollow(User $user, array $activity)
    {
        $actorUri = $activity['actor'] ?? null;
        
        \App\Models\Follower::where('user_id', $user->id)
            ->where('actor_uri', $actorUri)
            ->delete();

        return response()->json(['status' => 'unfollow_processed'], 202);
    }
}
