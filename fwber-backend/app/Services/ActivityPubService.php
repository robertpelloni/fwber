<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ActivityPubService
{
    public function __construct(
        protected ActivityPubKeyService $activityPubKeyService,
        protected HttpSignatureService $httpSignatureService
    ) {}

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
            $this->dispatchToRemoteInbox($user, $follower->actor_uri, $activity);
        }
    }

    /**
     * Dispatch a signed ActivityPub payload to a remote Inbox.
     */
    public function dispatchToRemoteInbox(User $user, string $actorUri, array $activity): void
    {
        $actorResponse = Http::accept('application/activity+json')
            ->withHeaders(['Accept' => 'application/activity+json, application/ld+json'])
            ->timeout(5)
            ->get($actorUri);

        if (! $actorResponse->successful()) {
            throw new RuntimeException("Unable to resolve ActivityPub actor {$actorUri}.");
        }

        $actorDocument = $actorResponse->json();
        $inboxUrl = is_array($actorDocument) ? ($actorDocument['inbox'] ?? null) : null;

        if (! is_string($inboxUrl) || trim($inboxUrl) === '') {
            throw new RuntimeException("Remote actor {$actorUri} did not publish an inbox URL.");
        }

        $body = json_encode($activity, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (! is_string($body)) {
            throw new RuntimeException('Unable to encode ActivityPub payload.');
        }

        $headers = $this->httpSignatureService->buildSignedHeaders(
            $this->activityPubKeyService->keyId($user),
            $this->activityPubKeyService->getPrivateKey($user),
            'POST',
            $inboxUrl,
            $body
        );

        $response = Http::withHeaders($headers)
            ->withBody($body, 'application/activity+json')
            ->send('POST', $inboxUrl);

        if (! $response->successful()) {
            throw new RuntimeException("Remote inbox {$inboxUrl} rejected '{$activity['type']}' with status {$response->status()}.");
        }

        Log::info("ActivityPub: Dispatched signed activity type '{$activity['type']}' to {$inboxUrl}");
    }

    /**
     * Generate the standard Person JSON-LD for an Actor.
     * Now includes custom 'fwber' dating extensions.
     */
    public function generateActorPayload(User $user): array
    {
        $profile = $user->profile;
        $actorUri = $this->activityPubKeyService->actorUri($user);
        $inboxUri = url("/api/federation/users/{$user->id}/inbox");
        $outboxUri = url("/api/federation/users/{$user->id}/outbox");
        $publicKeyId = $this->activityPubKeyService->keyId($user);
        $publicKeyPem = $this->activityPubKeyService->getPublicKey($user);

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
                'publicKeyPem' => $publicKeyPem,
            ],
        ];
    }
}
