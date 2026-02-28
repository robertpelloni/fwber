<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class NodeInfoController extends Controller
{
    /**
     * /.well-known/nodeinfo
     * Links to the actual NodeInfo schema document.
     */
    public function index()
    {
        return response()->json([
            'links' => [
                [
                    'rel' => 'http://nodeinfo.diaspora.software/ns/schema/2.0',
                    'href' => url('/nodeinfo/2.0')
                ]
            ]
        ]);
    }

    /**
     * /nodeinfo/2.0
     * Returns server capability details.
     */
    public function schema20()
    {
        // Count active users (has logged in last 6 months)
        $activeTotal = User::where('last_active_at', '>=', now()->subMonths(6))->count();
        $activeHalfYear = $activeTotal;
        $activeMonth = User::where('last_active_at', '>=', now()->subDays(30))->count();

        // Count federated users specifically
        $federatedCount = User::whereHas('profile', function ($q) {
            $q->where('is_federated', true);
        })->count();

        return response()->json([
            'version' => '2.0',
            'software' => [
                'name' => 'fwber',
                'version' => config('app.version', '0.3.47')
            ],
            'protocols' => [
                'activitypub'
            ],
            'services' => [
                'inbound' => [],
                'outbound' => []
            ],
            'openRegistrations' => true,
            'usage' => [
                'users' => [
                    'total' => $federatedCount, // Only expose count of opted-in users to Fediverse
                    'activeHalfyear' => $activeHalfYear,
                    'activeMonth' => $activeMonth
                ],
                'localPosts' => 0 // Might track Local Pulse artifacts shared globally later
            ],
            'metadata' => [
                'theme' => 'dark_glassmorphism',
                'focus' => 'proximity_dating'
            ]
        ])->header('Content-Type', 'application/json; profile="http://nodeinfo.diaspora.software/ns/schema/2.0#"');
    }
}
