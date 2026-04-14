<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityPubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ActivityPubAuthController extends Controller
{
    /**
     * Initiate a federated login challenge.
     */
    public function challenge(Request $request): JsonResponse
    {
        $handle = $request->input('handle'); // e.g. @user@mastodon.social
        
        if (!Str::contains($handle, '@')) {
            return response()->json(['error' => 'Invalid handle'], 422);
        }

        $cleanHandle = ltrim($handle, '@');
        [$username, $domain] = explode('@', $cleanHandle);

        // 1. Resolve Actor URI via WebFinger
        $webfingerUrl = "https://{$domain}/.well-known/webfinger?resource=acct:{$cleanHandle}";
        $response = Http::get($webfingerUrl);
        
        if (!$response->successful()) {
            return response()->json(['error' => 'Could not resolve handle'], 404);
        }

        $actorUri = null;
        foreach ($response->json('links') as $link) {
            if (($link['rel'] ?? '') === 'self' && ($link['type'] ?? '') === 'application/activity+json') {
                $actorUri = $link['href'];
                break;
            }
        }

        if (!$actorUri) {
            return response()->json(['error' => 'ActivityPub Actor not found'], 404);
        }

        // 2. Generate a random challenge token
        $challenge = Str::random(32);
        Cache::put("ap_auth_challenge:{$actorUri}", $challenge, now()->addMinutes(10));

        // 3. Return the challenge to the user
        // The user must now post this token in their Actor summary or send a signed activity
        return response()->json([
            'actor_uri' => $actorUri,
            'challenge' => $challenge,
            'instruction' => "To verify your identity, send a signed ActivityPub 'Verify' activity containing this token to our inbox, or temporarily add the token to your profile summary."
        ]);
    }

    /**
     * Verify the challenge and issue a JWT.
     */
    public function verify(Request $request): JsonResponse
    {
        $actorUri = $request->input('actor_uri');
        $storedChallenge = Cache::get("ap_auth_challenge:{$actorUri}");

        if (!$storedChallenge) {
            return response()->json(['error' => 'Challenge expired or not found'], 400);
        }

        // 1. Fetch remote Actor profile to check summary (Simple proof of ownership)
        $actorResponse = Http::withHeaders(['Accept' => 'application/activity+json'])->get($actorUri);
        $actor = $actorResponse->json();
        
        $summary = $actor['summary'] ?? '';
        
        if (!Str::contains($summary, $storedChallenge)) {
            return response()->json(['error' => 'Challenge token not found in Actor profile'], 403);
        }

        // 2. Identity verified! Create/get Shadow User
        $user = User::updateOrCreate(
            ['actor_uri' => $actorUri],
            [
                'name' => $actor['preferredUsername'] ?? 'remote_user',
                'email' => $actor['preferredUsername'] . '@' . parse_url($actorUri, PHP_URL_HOST),
                'password' => bcrypt(Str::random(32)), // Random password for shadow users
                'is_remote' => true,
            ]
        );

        // 3. Ensure profile exists
        if (!$user->profile) {
            $user->profile()->create([
                'display_name' => $actor['name'] ?? $user->name,
                'is_federated' => true,
            ]);
        }

        // 4. Generate Token
        $token = $user->createToken('federated-auth')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user->load('profile')
        ]);
    }
}
