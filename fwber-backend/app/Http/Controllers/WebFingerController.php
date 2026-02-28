<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class WebFingerController extends Controller
{
    /**
     * Resolves acct:username@domain.com into an ActivityPub Actor URI
     * 
     * @see https://www.rfc-editor.org/rfc/rfc7033
     */
    public function handle(Request $request)
    {
        $resource = $request->query('resource');

        if (!$resource || !str_starts_with($resource, 'acct:')) {
            return response()->json(['error' => 'Invalid resource string'], 400);
        }

        // Extract: acct:username@domain.test -> username
        $identifier = str_replace('acct:', '', $resource);
        $parts = explode('@', $identifier);
        
        if (count($parts) !== 2) {
             return response()->json(['error' => 'Malformed account string'], 400);
        }

        $username = $parts[0];
        $domain = $parts[1];

        // Ensure they are querying our actual domain
        if ($domain !== parse_url(config('app.url'), PHP_URL_HOST)) {
            return response()->json(['error' => 'Domain mismatch'], 404);
        }

        // Find user by name (or custom handle if we add it)
        $user = User::where('name', $username)
                    ->whereHas('profile', function($q) {
                        $q->where('is_federated', true); // Must opt-in
                    })->first();

        if (!$user) {
            return response()->json(['error' => 'User not found or not federated'], 404);
        }

        $actorUri = url("/api/federation/users/{$user->id}");

        return response()->json([
            'subject' => $resource,
            'aliases' => [
                $actorUri,
                url("/profile/{$user->id}")
            ],
            'links' => [
                [
                    'rel' => 'self',
                    'type' => 'application/activity+json',
                    'href' => $actorUri
                ],
                [
                    'rel' => 'http://webfinger.net/rel/profile-page',
                    'type' => 'text/html',
                    'href' => url("/profile/{$user->id}")
                ]
            ]
        ])->header('Content-Type', 'application/jrd+json');
    }
}
