<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Schema;

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
                    'href' => url('/nodeinfo/2.0'),
                ],
            ],
        ]);
    }

    /**
     * /nodeinfo/2.0
     * Returns server capability details.
     */
    public function schema20()
    {
        // Discovery routes should stay up even when production schema recovery is
        // still in progress. Missing optional columns/tables degrade to zero.
        $activeHalfYear = 0;
        $activeMonth = 0;

        if (Schema::hasColumn('users', 'last_active_at')) {
            $activeHalfYear = User::where('last_active_at', '>=', now()->subMonths(6))->count();
            $activeMonth = User::where('last_active_at', '>=', now()->subDays(30))->count();
        }

        $federatedCount = 0;
        if (Schema::hasTable('user_profiles') && Schema::hasColumn('user_profiles', 'is_federated')) {
            $federatedCount = User::whereHas('profile', function ($query) {
                $query->where('is_federated', true);
            })->count();
        }

        return response()->json([
            'version' => '2.0',
            'software' => [
                'name' => 'fwber',
                'version' => config('app.version', '0.3.47'),
            ],
            'protocols' => [
                'activitypub',
            ],
            'services' => [
                'inbound' => [],
                'outbound' => [],
            ],
            'openRegistrations' => true,
            'usage' => [
                'users' => [
                    'total' => $federatedCount,
                    'activeHalfyear' => $activeHalfYear,
                    'activeMonth' => $activeMonth,
                ],
                'localPosts' => 0,
            ],
            'metadata' => [
                'theme' => 'dark_glassmorphism',
                'focus' => 'proximity_dating',
            ],
        ])->header('Content-Type', 'application/json; profile="http://nodeinfo.diaspora.software/ns/schema/2.0#"');
    }
}
