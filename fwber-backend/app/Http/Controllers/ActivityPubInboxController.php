<?php

namespace App\Http\Controllers;

use App\Models\Following;
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

            case 'Accept':
                if (($activity['object']['type'] ?? null) === 'Follow') {
                    return $this->handleAccept($user, $activity);
                }
                break;

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

        $actorUri = $activity['actor'] ?? null;
        
        // --- SECURE FEDERATED DM LOGIC ---
        $to = $object['to'] ?? [];
        if (is_string($to)) $to = [$to];
        
        $isPublic = false;
        foreach ($to as $recipient) {
            if ($recipient === 'https://www.w3.org/ns/activitystreams#Public' || str_contains($recipient, '#Public')) {
                $isPublic = true;
                break;
            }
        }

        if (!$isPublic) {
            // This is likely a Direct Message (DM)
            return $this->handleDirectMessage($user, $activity, $object);
        }
        // ---------------------------------

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
     * Handle incoming Federated DM
     */
    protected function handleDirectMessage(User $user, array $activity, array $object)
    {
        $actorUri = $activity['actor'];
        
        // We'll store it as a special type of Message
        // For now, we reuse the existing messages table but with a federated_actor_uri
        \App\Models\Message::create([
            'sender_id' => 0, // System/Federated ID
            'receiver_id' => $user->id,
            'content' => $object['content'] ?? '[Encrypted Content]',
            'message_type' => 'federated_dm',
            'is_encrypted' => isset($object['content']) && str_contains($object['content'], 'cipher'),
            'sent_at' => isset($object['published']) ? \Carbon\Carbon::parse($object['published']) : now(),
            'metadata' => json_encode([
                'actor_uri' => $actorUri,
                'guid' => $object['id']
            ])
        ]);

        return response()->json(['status' => 'dm_stored'], 202);
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
     * Process an incoming Accept activity for a follow initiated by this user.
     */
    protected function handleAccept(User $user, array $activity)
    {
        $actorUri = $activity['actor'] ?? null;
        $object = $activity['object'] ?? null;
        $expectedActorUri = url("/api/federation/users/{$user->id}");

        if (! $actorUri || ! is_array($object)) {
            return response()->json(['error' => 'Malformed Accept activity'], 400);
        }

        if (($object['type'] ?? null) !== 'Follow' || ($object['actor'] ?? null) !== $expectedActorUri) {
            return response()->json(['status' => 'ignored_accept'], 202);
        }

        $followedActorUri = $object['object'] ?? null;
        if (! is_string($followedActorUri) || $followedActorUri !== $actorUri) {
            return response()->json(['status' => 'ignored_accept'], 202);
        }

        $following = Following::where('user_id', $user->id)
            ->where('actor_uri', $actorUri)
            ->first();

        if (! $following) {
            return response()->json(['status' => 'accept_without_follow'], 202);
        }

        $following->update(['status' => 'accepted']);

        Log::info("ActivityPub: Follow accepted for user {$user->id} by {$actorUri}");

        return response()->json(['status' => 'accept_processed'], 202);
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
