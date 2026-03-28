<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActivityPubInboxController extends Controller
{
    /**
     * Accepts POST requests from other federated servers.
     *
     * @param  int  $id  The local user ID receiving the activity.
     */
    public function handle(Request $request, $id)
    {
        $user = User::where('id', $id)
            ->whereHas('profile', function ($q) {
                $q->where('is_federated', true);
            })->first();

        if (! $user) {
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
                return $this->handleCreate($user, $activity);
        }

        return response()->json(['status' => 'received'], 202);
    }

    /**
     * Process an incoming Create activity (Note/Post).
     */
    protected function handleCreate(User $user, array $activity)
    {
        $object = $activity['object'] ?? null;
        if (! $object || ($object['type'] ?? '') !== 'Note') {
            return response()->json(['status' => 'ignored_type'], 202);
        }

        // Only store if we are actually following this person (or they are a follower? usually following)
        // For simplicity in this demo, we store it if it's sent to us.

        $actorUri = $activity['actor'] ?? null;
        $parsed = parse_url($actorUri);
        $domain = $parsed['host'] ?? null;
        $pathParts = explode('/', trim($parsed['path'] ?? '', '/'));
        $username = end($pathParts);

        \App\Models\FederatedPost::updateOrCreate(
            ['guid' => $object['id']],
            [
                'actor_uri' => $actorUri,
                'actor_username' => $username,
                'actor_domain' => $domain,
                'content' => $object['content'] ?? '',
                'url' => $object['url'] ?? null,
                'published_at' => isset($object['published']) ? \Carbon\Carbon::parse($object['published']) : now(),
                'metadata' => $object,
            ]
        );

        return response()->json(['status' => 'post_stored'], 202);
    }

    /**
     * Process an incoming Follow activity.
     */
    protected function handleFollow(User $user, array $activity)
    {
        $actorUri = $activity['actor'] ?? null;
        if (! $actorUri) {
            return response()->json(['error' => 'No actor provided'], 400);
        }

        $parsed = parse_url($actorUri);
        $domain = $parsed['host'] ?? null;
        $pathParts = explode('/', trim($parsed['path'] ?? '', '/'));
        $username = end($pathParts);

        \App\Models\Follower::updateOrCreate(
            ['user_id' => $user->id, 'actor_uri' => $actorUri],
            [
                'username' => $username,
                'domain' => $domain,
                'status' => 'accepted',
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
