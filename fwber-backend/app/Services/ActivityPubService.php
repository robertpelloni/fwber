<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class ActivityPubService
{
    /**
     * Broadcast an activity to all accepted followers.
     */
    public function broadcastToFollowers(User $user, array $activity): void
    {
        // Get all followers with actor URIs
        $followers = $user->followers()
            ->wherePivot('status', 'accepted')
            ->whereNotNull('actor_uri')
            ->get();

        foreach ($followers as $follower) {
            $this->dispatchToRemoteInbox($follower->actor_uri, $activity);
        }
    }

    /**
     * Dispatch an ActivityPub payload to a remote Inbox.
     * In a real implementation, this would use a signed HTTP request.
     */
    public function dispatchToRemoteInbox(string $actorUri, array $activity): void
    {
        // 1. Resolve Inbox from Actor URI (WebFinger or Actor fetch)
        // 2. Sign with User's Private Key
        // 3. POST to remote server

        Log::info("ActivityPub: Dispatching activity type '{$activity['type']}' to {$actorUri}");

        // Mocking the actual network call
        // Http::withHeaders(['Signature' => '...'])->post($inbox, $activity);
    }

    /**
     * Generate the standard Person JSON-LD for an Actor.
     * Now includes custom 'fwber' dating extensions.
     */
    public function generateActorPayload(User $user): array
    {
        $profile = $user->profile;
        $actorUri = url("/api/federation/users/{$user->id}");
        $inboxUri = url("/api/federation/users/{$user->id}/inbox");
        $outboxUri = url("/api/federation/users/{$user->id}/outbox");
        $publicKeyId = "{$actorUri}#main-key";

        return [
            '@context' => [
                'https://www.w3.org/ns/activitystreams',
                'https://w3id.org/security/v1',
                [
                    'fwber' => 'https://schema.fwber.app/ns#',
                    'gender' => 'fwber:gender',
                    'orientation' => 'fwber:orientation',
                    'relationshipStatus' => 'fwber:relationshipStatus',
                    'isVerified' => 'fwber:isVerified',
                ],
            ],
            'id' => $actorUri,
            'type' => 'Person',
            'preferredUsername' => $user->name,
            'name' => $profile->display_name ?? $user->name,
            'summary' => $profile->bio ?? 'Hi, I am on fwber!',
            'url' => url("/profile/{$user->id}"),
            'icon' => [
                'type' => 'Image',
                'mediaType' => 'image/jpeg',
                'url' => $user->photos()->where('is_primary', true)->first()?->url ?? asset('images/default-avatar.png'),
            ],
            // Dating Specific Metadata (fwber namespace)
            'gender' => $profile->gender ?? 'unspecified',
            'orientation' => $profile->sexual_orientation ?? 'unspecified',
            'relationshipStatus' => $profile->relationship_type ?? 'single',
            'isVerified' => (bool) ($profile->is_id_verified ?? false),
            'inbox' => $inboxUri,
            'outbox' => $outboxUri,
            'publicKey' => [
                'id' => $publicKeyId,
                'owner' => $actorUri,
                'publicKeyPem' => "-----BEGIN PUBLIC KEY-----\nMockPublicKeyForIteration0347\n-----END PUBLIC KEY-----",
            ],
        ];
    }
}
