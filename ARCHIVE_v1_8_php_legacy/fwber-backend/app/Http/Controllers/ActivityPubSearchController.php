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
     * Search for external actors. 
     * Supports both direct handles (@user@domain) and keyword searches across discovery hubs.
     */
    public function search(Request $request)
    {
        $query = $request->query('q');

        if (! $query) {
            return response()->json(['error' => 'Query required'], 422);
        }

        // Case 1: Direct WebFinger Handle
        if (str_contains($query, '@')) {
            return $this->searchHandle($query);
        }

        // Case 2: Keyword search across configured discovery hubs
        return $this->searchKeywords($query);
    }

    protected function searchHandle(string $handle)
    {
        // Normalize handle (remove leading @)
        $cleanHandle = ltrim($handle, '@');
        $parts = explode('@', $cleanHandle);
        
        if (count($parts) !== 2) {
            return response()->json(['actors' => []]);
        }
        
        [$username, $domain] = $parts;

        try {
            // 1. WebFinger Lookup
            $webfingerUrl = "https://{$domain}/.well-known/webfinger?resource=acct:{$cleanHandle}";
            $webfingerResponse = Http::timeout(5)->get($webfingerUrl);

            if (! $webfingerResponse->successful()) {
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

            return response()->json([
                'actors' => [
                    $this->formatActor($actor, $domain, $username)
                ],
            ]);

        } catch (\Exception $e) {
            Log::error("Federated handle search exception: ".$e->getMessage());
            return response()->json(['actors' => []]);
        }
    }

    protected function searchKeywords(string $keywords)
    {
        // Configured discovery hubs (e.g. major Mastodon/Pleroma instances)
        $hubs = config('federation.discovery_hubs', ['mastodon.social', 'fosstodon.org']);
        
        try {
            $responses = Http::pool(fn ($pool) => 
                collect($hubs)->map(fn ($hub) => 
                    $pool->as($hub)->timeout(3)->get("https://{$hub}/api/v1/accounts/search", [
                        'q' => $keywords,
                        'limit' => 5,
                        'resolve' => 'false'
                    ])
                )
            );

            $results = [];
            foreach ($responses as $hub => $response) {
                if ($response->successful()) {
                    $actors = $response->json();
                    if (is_array($actors)) {
                        foreach ($actors as $actor) {
                            $results[] = [
                                'id' => $actor['url'] ?? $actor['id'],
                                'type' => 'Person',
                                'preferredUsername' => $actor['username'],
                                'name' => $actor['display_name'] ?? $actor['username'],
                                'summary' => strip_tags($actor['note'] ?? ''),
                                'icon' => isset($actor['avatar']) ? ['url' => $actor['avatar']] : null,
                                'server' => $hub,
                                'is_remote' => true
                            ];
                        }
                    }
                }
            }

            return response()->json(['actors' => $results]);

        } catch (\Exception $e) {
            Log::error("Federated keyword search exception: ".$e->getMessage());
            return response()->json(['actors' => []]);
        }
    }

    protected function formatActor(array $actor, string $domain, string $fallbackUsername): array
    {
        return [
            'id' => $actor['id'] ?? null,
            'type' => $actor['type'] ?? 'Person',
            'preferredUsername' => $actor['preferredUsername'] ?? $fallbackUsername,
            'name' => $actor['name'] ?? $fallbackUsername,
            'summary' => strip_tags($actor['summary'] ?? ''),
            'icon' => isset($actor['icon']['url']) ? ['url' => $actor['icon']['url']] : null,
            'server' => $domain,
            'is_remote' => true
        ];
    }

    /**
     * Follow an external actor.
     */
    public function follow(Request $request)
    {
        $user = auth()->user();
        $actorId = $request->input('actor_id');
        $actorType = $request->input('actor_type', 'Person');

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
                    'actor_type' => $actorType,
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
