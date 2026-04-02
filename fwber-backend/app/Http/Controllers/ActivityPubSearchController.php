<?php

namespace App\Http\Controllers;

use App\Models\FederatedPost;
use App\Services\ActivityPubKeyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ActivityPubSearchController extends Controller
{
    /**
     * Search for an external actor using WebFinger.
     *
     * Expected format: @user@domain.com or user@domain.com
     */
    public function search(Request $request)
    {
        $handle = $request->query('q');

        if (! $handle || ! str_contains($handle, '@')) {
            return response()->json(['error' => 'Invalid handle format'], 422);
        }

        // Normalize handle (remove leading @)
        $cleanHandle = ltrim($handle, '@');
        [$username, $domain] = explode('@', $cleanHandle);

        try {
            // 1. WebFinger Lookup
            $webfingerUrl = "https://{$domain}/.well-known/webfinger?resource=acct:{$cleanHandle}";
            $webfingerResponse = Http::timeout(5)->get($webfingerUrl);

            if (! $webfingerResponse->successful()) {
                Log::warning("WebFinger lookup failed for {$handle}", ['url' => $webfingerUrl]);

                return response()->json(['actors' => []]);
            }

            $data = $webfingerResponse->json();
            $actorUrl = null;

            foreach ($data['links'] ?? [] as $link) {
                if (($link['rel'] ?? '') === 'self' && ($link['type'] ?? '') === 'application/activity+json') {
                    $actorUrl = $link['href'];
                    break;
                }
            }

            if (! $actorUrl) {
                return response()->json(['actors' => []]);
            }

            // 2. Fetch Actor Profile
            $actorResponse = Http::timeout(5)
                ->withHeaders(['Accept' => 'application/activity+json'])
                ->get($actorUrl);

            if (! $actorResponse->successful()) {
                return response()->json(['actors' => []]);
            }

            $actor = $actorResponse->json();

            // 3. Format for Frontend
            return response()->json([
                'actors' => [
                    [
                        'id' => $actor['id'] ?? $actorUrl,
                        'type' => $actor['type'] ?? 'Person',
                        'preferredUsername' => $actor['preferredUsername'] ?? $username,
                        'name' => $actor['name'] ?? $username,
                        'summary' => strip_tags($actor['summary'] ?? ''),
                        'icon' => isset($actor['icon']['url']) ? ['url' => $actor['icon']['url']] : null,
                        'server' => $domain,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error("Federated search exception for {$handle}: ".$e->getMessage());

            return response()->json(['actors' => []]);
        }
    }

    /**
     * Follow an external actor.
     */
    public function follow(Request $request)
    {
        $user = auth()->user();
        $actorId = $request->input('actor_id');

        if (! $user) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        if (! $actorId) {
            return response()->json(['error' => 'Actor ID required'], 422);
        }

        if (! ($user->profile?->is_federated)) {
            return response()->json(['error' => 'Federation must be enabled before following remote actors'], 422);
        }

        try {
            // Parse actor ID to get username and domain for local record
            $parsed = parse_url($actorId);
            $domain = $parsed['host'] ?? null;
            $pathParts = explode('/', trim($parsed['path'] ?? '', '/'));
            $username = end($pathParts);

            // 1. Store the follow record locally
            \App\Models\Following::updateOrCreate(
                ['user_id' => $user->id, 'actor_uri' => $actorId],
                [
                    'username' => $username,
                    'domain' => $domain,
                    'status' => 'pending',
                ]
            );

            // 2. Log the follow intent
            Log::info("User {$user->id} requested to follow federated actor: {$actorId}");

            // 3. Sign and dispatch 'Follow' activity to remote inbox
            $activityPubService = app(\App\Services\ActivityPubService::class);
            $activityPubKeyService = app(ActivityPubKeyService::class);
            $activityPubService->dispatchToRemoteInbox($user, $actorId, [
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'type' => 'Follow',
                'actor' => $activityPubKeyService->actorUri($user),
                'object' => $actorId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Follow request initiated',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to initiate federated follow: '.$e->getMessage());

            return response()->json(['error' => 'Failed to process follow request'], 500);
        }
    }

    /**
     * Get list of external actors the user is following.
     */
    public function getFollowing()
    {
        $user = auth()->user();
        $following = \App\Models\Following::where('user_id', $user->id)->get();

        return response()->json([
            'following' => $following,
        ]);
    }

    /**
     * Get list of external actors following the user.
     */
    public function getFollowers()
    {
        $user = auth()->user();
        $followers = \App\Models\Follower::where('user_id', $user->id)->get();

        return response()->json([
            'followers' => $followers,
        ]);
    }

    /**
     * Get recent federated posts.
     */
    public function getPosts(Request $request)
    {
        $limit = min(max((int) $request->query('limit', 50), 1), 100);
        $actorUri = $request->query('actor_uri');

        $posts = FederatedPost::query()
            ->when($actorUri, fn ($query) => $query->where('actor_uri', $actorUri))
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'posts' => $posts,
        ]);
    }
}
