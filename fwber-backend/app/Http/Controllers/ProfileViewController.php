<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProfileViewController extends Controller
{
    /**
     * Record a profile view
     */
    public function recordView(Request $request, $userId)
    {
        $viewerUserId = $request->user() ? $request->user()->id : null;
        $viewerIp = $request->ip();
        $userAgent = $request->userAgent();

        // Don't record if user is viewing their own profile
        if ($viewerUserId && $viewerUserId == $userId) {
            return response()->json(['message' => 'Cannot view own profile']);
        }

        // Check if view already exists in last 24 hours
        $existingView = DB::table('profile_views')
            ->where('viewed_user_id', $userId)
            ->where(function ($query) use ($viewerUserId, $viewerIp) {
                if ($viewerUserId) {
                    $query->where('viewer_user_id', $viewerUserId);
                } else {
                    $query->where('viewer_ip', $viewerIp);
                }
            })
            ->where('created_at', '>', Carbon::now()->subHours(24))
            ->first();

        if ($existingView) {
            return response()->json(['message' => 'View already recorded']);
        }

        // Record the view
        DB::table('profile_views')->insert([
            'viewed_user_id' => $userId,
            'viewer_user_id' => $viewerUserId,
            'viewer_ip' => $viewerIp,
            'user_agent' => substr($userAgent, 0, 255),
            'created_at' => Carbon::now(),
        ]);

        return response()->json(['message' => 'Profile view recorded']);
    }

    /**
     * Get profile views for a user
     */
    public function getViews(Request $request, $userId)
    {
        // Only allow users to view their own profile views
        if ($request->user()->id != $userId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $views = DB::table('profile_views')
            ->where('viewed_user_id', $userId)
            ->whereNotNull('viewer_user_id')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $viewsWithUsers = [];
        foreach ($views as $view) {
            $viewer = DB::table('users')->find($view->viewer_user_id);
            if ($viewer) {
                $viewsWithUsers[] = [
                    'id' => $view->id,
                    'viewer' => [
                        'id' => $viewer->id,
                        'name' => $viewer->name,
                        'avatar_url' => $viewer->avatar_url,
                        'age' => $viewer->age,
                        'city' => $viewer->city,
                    ],
                    'viewed_at' => $view->created_at,
                ];
            }
        }

        return response()->json($viewsWithUsers);
    }

    /**
     * Get profile view statistics
     */
    public function getStats(Request $request, $userId)
    {
        // Only allow users to view their own stats
        if ($request->user()->id != $userId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $totalViews = DB::table('profile_views')
            ->where('viewed_user_id', $userId)
            ->count();

        $todayViews = DB::table('profile_views')
            ->where('viewed_user_id', $userId)
            ->whereDate('created_at', Carbon::today())
            ->count();

        $weekViews = DB::table('profile_views')
            ->where('viewed_user_id', $userId)
            ->where('created_at', '>', Carbon::now()->subWeek())
            ->count();

        $monthViews = DB::table('profile_views')
            ->where('viewed_user_id', $userId)
            ->where('created_at', '>', Carbon::now()->subMonth())
            ->count();

        $uniqueViewers = DB::table('profile_views')
            ->where('viewed_user_id', $userId)
            ->whereNotNull('viewer_user_id')
            ->distinct('viewer_user_id')
            ->count();

        return response()->json([
            'total_views' => $totalViews,
            'today_views' => $todayViews,
            'week_views' => $weekViews,
            'month_views' => $monthViews,
            'unique_viewers' => $uniqueViewers,
        ]);
    }
}
