<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebFingerController extends Controller
{
    /**
     * Minimal WebFinger responder for local acct: lookups.
     *
     * This endpoint exists primarily to stop the public discovery route from
     * exploding in production when route caches or tooling evaluate the web
     * route set. The broader federation surface can evolve later, but this
     * controller keeps the endpoint honest and stable right now.
     */
    public function handle(Request $request): JsonResponse
    {
        $resource = (string) $request->query('resource', '');

        if ($resource === '') {
            return response()
                ->json(['error' => 'missing_resource'], 400)
                ->header('Content-Type', 'application/jrd+json');
        }

        $expectedHost = parse_url(config('app.url', ''), PHP_URL_HOST) ?: $request->getHost();

        if (! Str::startsWith($resource, 'acct:') || ! Str::endsWith($resource, '@'.$expectedHost)) {
            return response()
                ->json(['error' => 'resource_not_found'], 404)
                ->header('Content-Type', 'application/jrd+json');
        }

        $username = Str::before(Str::after($resource, 'acct:'), '@');
        $user = User::query()
            ->where('name', $username)
            ->whereHas('profile', fn ($query) => $query->where('is_federated', true))
            ->first();

        if (! $user) {
            return response()
                ->json([
                    'subject' => $resource,
                    'links' => [],
                ], 404)
                ->header('Content-Type', 'application/jrd+json');
        }

        return response()
            ->json([
                'subject' => $resource,
                'links' => [
                    [
                        'rel' => 'self',
                        'type' => 'application/activity+json',
                        'href' => url('/api/federation/users/'.$user->id),
                    ],
                    [
                        'rel' => 'http://nodeinfo.diaspora.software/ns/schema/2.0',
                        'href' => url('/nodeinfo/2.0'),
                    ],
                ],
            ])
            ->header('Content-Type', 'application/jrd+json');
    }
}
