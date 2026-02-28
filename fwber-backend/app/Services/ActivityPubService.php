<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class ActivityPubService
{
    /**
     * Generate an ActivityPub Actor (Person) representation of a user.
     * Maps local user profile fields into standard fediverse schema.
     */
    public function generateActorPayload(User $user): array
    {
        $profile = $user->profile;
        
        // Essential endpoints
        $actorUri = url("/api/federation/users/{$user->id}");
        $inboxUri = url("/api/federation/users/{$user->id}/inbox");
        $outboxUri = url("/api/federation/users/{$user->id}/outbox");
        
        $publicKeyId = "{$actorUri}#main-key";
        
        // Return standard Person JSON-LD
        return [
            '@context' => [
                'https://www.w3.org/ns/activitystreams',
                'https://w3id.org/security/v1'
            ],
            'id' => $actorUri,
            'type' => 'Person',
            'preferredUsername' => $user->name,
            'name' => $profile->display_name ?? $user->name,
            'summary' => $profile->bio ?? 'Hi, I am on fwber!',
            'url' => url("/profile/{$user->id}"),
            // Icon / Avatar mapping
            'icon' => [
                'type' => 'Image',
                'mediaType' => 'image/jpeg',
                'url' => $user->photos()->where('is_primary', true)->first()?->url ?? asset('images/default-avatar.png')
            ],
            // Endpoints
            'inbox' => $inboxUri,
            'outbox' => $outboxUri,
            // (Placeholder) Public Key for HTTP Signatures.
            // Required for Mastodon to actually ingest our activities.
            'publicKey' => [
                'id' => $publicKeyId,
                'owner' => $actorUri,
                'publicKeyPem' => "-----BEGIN PUBLIC KEY-----\nMockPublicKeyForIteration0347\n-----END PUBLIC KEY-----"
            ]
        ];
    }
}
