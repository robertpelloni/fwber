<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProfileViewController extends Controller
{
    /**
     * @OA\Post(
     *     path="/profile/{userId}/view",
     *     tags={"Profile Views"},
     *     summary="Record a profile view",
     *     description="Track when a user views another user's profile (with 24-hour deduplication). Anonymous views are tracked by IP address. Returns one of three possible messages: 'Profile view recorded' (new view), 'View already recorded' (duplicate within 24h), or 'Cannot view own profile' (self-view blocked).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="ID of the user whose profile is being viewed",
     *         required=true,
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operation completed (see message field for details)",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Profile view recorded")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/profile/{userId}/views",
     *     tags={"Profile Views"},
     *     summary="Get profile viewers",
     *     description="Retrieve list of authenticated users who have viewed the profile (last 50 views, excludes anonymous views)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="ID of the user whose profile views to retrieve",
     *         required=true,
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile viewers retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(
     *                     property="viewer",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=67),
     *                     @OA\Property(property="name", type="string", example="Jane Smith"),
     *                     @OA\Property(property="avatar_url", type="string", nullable=true, example="https://cdn.fwber.com/avatars/67.jpg"),
     *                     @OA\Property(property="age", type="integer", example=28),
     *                     @OA\Property(property="city", type="string", example="New York")
     *                 ),
     *                 @OA\Property(property="viewed_at", type="string", format="date-time", example="2025-01-15T14:30:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to view other user's profile views",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
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
     * @OA\Get(
     *     path="/profile/{userId}/view-stats",
     *     tags={"Profile Views"},
     *     summary="Get profile view statistics",
     *     description="Retrieve aggregated profile view analytics including total, daily, weekly, and monthly views plus unique viewer count",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="ID of the user whose view statistics to retrieve",
     *         required=true,
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="View statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="total_views", type="integer", example=156),
     *             @OA\Property(property="today_views", type="integer", example=12),
     *             @OA\Property(property="week_views", type="integer", example=45),
     *             @OA\Property(property="month_views", type="integer", example=98),
     *             @OA\Property(property="unique_viewers", type="integer", example=72)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to view other user's statistics",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
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
