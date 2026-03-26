<?php

namespace App\Http\Controllers;

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
        
        if (!$handle || !str_contains($handle, '@')) {
            return response()->json(['error' => 'Invalid handle format'], 422);
        }

        // Normalize handle (remove leading @)
        $cleanHandle = ltrim($handle, '@');
        [$username, $domain] = explode('@', $cleanHandle);

        try {
            // 1. WebFinger Lookup
            $webfingerUrl = "https://{$domain}/.well-known/webfinger?resource=acct:{$cleanHandle}";
            $webfingerResponse = Http::timeout(5)->get($webfingerUrl);

            if (!$webfingerResponse->successful()) {
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

            if (!$actorUrl) {
                return response()->json(['actors' => []]);
            }

            // 2. Fetch Actor Profile
            $actorResponse = Http::timeout(5)
                ->withHeaders(['Accept' => 'application/activity+json'])
                ->get($actorUrl);

            if (!$actorResponse->successful()) {
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
                        'server' => $domain
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Federated search exception for {$handle}: " . $e->getMessage());
            return response()->json(['actors' => []]);
        }
    }
}
