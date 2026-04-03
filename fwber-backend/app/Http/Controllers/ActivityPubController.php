<?php

namespace App\Http\Controllers;

use App\Models\FederatedPost;
use App\Models\Follower;
use App\Models\Following;
use App\Models\User;
use App\Services\ActivityPubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ActivityPubController extends Controller
{
    /**
     * Resolves the Actor profile via /api/federation/users/{id}
     */
    public function actor($id, ActivityPubService $service)
    {
        $user = User::where('id', $id)
            ->whereHas('profile', function ($q) {
                $q->where('is_federated', true);
            })->first();

        if (! $user) {
            return response()->json(['error' => 'Actor not found'], 404);
        }

        $payload = $service->generateActorPayload($user);

        return response()->json($payload)
            ->header('Content-Type', 'application/activity+json');
    }

    /**
     * Resolves the Group profile via /api/federation/groups/{id}
     */
    public function group($id, ActivityPubService $service)
    {
        $group = \App\Models\Group::where('id', $id)
            ->where('is_federated', true)
            ->first();

        if (! $group) {
            return response()->json(['error' => 'Group not found'], 404);
        }

        $payload = $service->generateGroupPayload($group);

        return response()->json($payload)
            ->header('Content-Type', 'application/activity+json');
    }

    /**
     * Resolve a remote actor for the authenticated federation UI.
     */
    public function actorDetail(Request $request)
    {
        $user = $request->user();
        $actorUri = $request->query('uri');

        if (! $actorUri || ! filter_var($actorUri, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'Valid actor URI required'], 422);
        }

        $parsed = parse_url($actorUri);
        $domain = $parsed['host'] ?? null;
        $pathParts = explode('/', trim($parsed['path'] ?? '', '/'));
        $username = end($pathParts) ?: 'remote-user';

        $cachedPostsQuery = FederatedPost::where('actor_uri', $actorUri);
        $latestCachedPost = (clone $cachedPostsQuery)
            ->orderBy('published_at', 'desc')
            ->first();

        $following = $user
            ? Following::where('user_id', $user->id)->where('actor_uri', $actorUri)->latest('updated_at')->first()
            : null;
        $follower = $user
            ? Follower::where('user_id', $user->id)->where('actor_uri', $actorUri)->latest('updated_at')->first()
            : null;

        $actor = null;
        $actorUrl = null;

        try {
            $actorResponse = Http::timeout(5)
                ->withHeaders(['Accept' => 'application/activity+json'])
                ->get($actorUri);

            if ($actorResponse->successful()) {
                $actor = $actorResponse->json();
            }
        } catch (\Throwable $e) {
            Log::warning("ActivityPub actor detail lookup failed for {$actorUri}: ".$e->getMessage());
        }

        $latestMetadata = $latestCachedPost?->metadata ?? [];

        if (is_string($actor['url'] ?? null)) {
            $actorUrl = $actor['url'];
        } elseif (is_array($actor['url'] ?? null)) {
            $actorUrl = $actor['url']['href']
                ?? $actor['url']['url']
                ?? $actor['url'][0]['href']
                ?? $actor['url'][0]['url']
                ?? null;
        }

        return response()->json([
            'actor' => [
                'id' => $actor['id'] ?? $actorUri,
                'type' => $actor['type'] ?? 'Person',
                'preferredUsername' => $actor['preferredUsername'] ?? $latestMetadata['preferredUsername'] ?? $following?->username ?? $follower?->username ?? $username,
                'name' => $actor['name'] ?? $latestMetadata['name'] ?? $following?->username ?? $follower?->username ?? $username,
                'summary' => strip_tags($actor['summary'] ?? $latestMetadata['summary'] ?? ''),
                'icon' => isset($actor['icon']['url'])
                    ? ['url' => $actor['icon']['url']]
                    : (isset($latestMetadata['icon']['url']) ? ['url' => $latestMetadata['icon']['url']] : null),
                'server' => $domain,
                'url' => $actorUrl,
                'inbox' => $actor['inbox'] ?? null,
                'outbox' => $actor['outbox'] ?? null,
                'isVerified' => (bool) ($actor['isVerified'] ?? false),
                'gender' => $actor['gender'] ?? null,
                'orientation' => $actor['orientation'] ?? null,
                'relationshipStatus' => $actor['relationshipStatus'] ?? null,
                'reputation' => $actor['reputation'] ?? null,
                'cachedPostsCount' => (clone $cachedPostsQuery)->count(),
                'lastCachedPostAt' => $latestCachedPost?->published_at?->toIso8601String(),
                'followingStatus' => $following?->status,
                'followerStatus' => $follower?->status,
            ],
        ]);
    }
}
