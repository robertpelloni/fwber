<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/dashboard/stats",
     *     tags={"Dashboard"},
     *     summary="Get dashboard statistics",
     *     description="Retrieve comprehensive statistics for the authenticated user including matches, conversations, profile views, and engagement metrics",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="total_matches", type="integer", example=15),
     *             @OA\Property(property="pending_matches", type="integer", example=3),
     *             @OA\Property(property="accepted_matches", type="integer", example=12),
     *             @OA\Property(property="conversations", type="integer", example=8),
     *             @OA\Property(property="profile_views", type="integer", example=42),
     *             @OA\Property(property="today_views", type="integer", example=5),
     *             @OA\Property(property="match_score_avg", type="integer", example=85),
     *             @OA\Property(property="response_rate", type="integer", example=75),
     *             @OA\Property(property="days_active", type="integer", example=14),
     *             @OA\Property(property="last_login", type="string", format="date-time", example="2025-01-15T10:30:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     */
    public function getStats(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;

        // Get match statistics
        $matchStats = DB::table('matches')
            ->where(function ($query) use ($userId) {
                $query->where('user1_id', $userId)
                      ->orWhere('user2_id', $userId);
            })
            ->selectRaw('
                COUNT(*) as total_matches,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_matches,
                SUM(CASE WHEN status = "accepted" THEN 1 ELSE 0 END) as accepted_matches,
                AVG(match_score) as match_score_avg
            ')
            ->first();

        // Get conversation count (unique conversations)
        $conversationCount = DB::table('messages')
            ->where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->distinct()
            ->count(DB::raw('LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)'));

        // Get profile views
        $profileViews = DB::table('profile_views')
            ->where('viewed_user_id', $userId)
            ->count();

        $todayViews = DB::table('profile_views')
            ->where('viewed_user_id', $userId)
            ->whereDate('created_at', Carbon::today())
            ->count();

        // Calculate response rate
        $sentMessages = DB::table('messages')
            ->where('sender_id', $userId)
            ->count();

        $receivedMessages = DB::table('messages')
            ->where('receiver_id', $userId)
            ->count();

        $responseRate = $receivedMessages > 0 
            ? round(($sentMessages / $receivedMessages) * 100) 
            : 0;

        // Days active
        $daysActive = Carbon::parse($user->created_at)->diffInDays(Carbon::now());

        return response()->json([
            'total_matches' => (int) ($matchStats->total_matches ?? 0),
            'pending_matches' => (int) ($matchStats->pending_matches ?? 0),
            'accepted_matches' => (int) ($matchStats->accepted_matches ?? 0),
            'conversations' => (int) $conversationCount,
            'profile_views' => (int) $profileViews,
            'today_views' => (int) $todayViews,
            'match_score_avg' => (int) round($matchStats->match_score_avg ?? 0),
            'response_rate' => (int) $responseRate,
            'days_active' => (int) $daysActive,
            'last_login' => $user->updated_at->toISOString(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/dashboard/activity",
     *     tags={"Dashboard"},
     *     summary="Get recent activity feed",
     *     description="Retrieve a unified activity feed showing recent matches, messages, and profile views",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Maximum number of activity items to return",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, minimum=1, maximum=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Activity feed retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="type", type="string", enum={"match", "message", "view"}, example="match"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=42),
     *                     @OA\Property(property="name", type="string", example="Jane Smith"),
     *                     @OA\Property(property="avatar_url", type="string", nullable=true, example="https://cdn.fwber.com/avatars/42.jpg")
     *                 ),
     *                 @OA\Property(property="timestamp", type="string", format="date-time", example="2025-01-15T09:45:00Z"),
     *                 @OA\Property(property="match_score", type="integer", nullable=true, example=85, description="Only present for match type activities")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     */
    public function getActivity(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;
        $limit = $request->input('limit', 10);

        $activities = [];

        // Recent matches
        $matches = DB::table('matches')
            ->where(function ($query) use ($userId) {
                $query->where('user1_id', $userId)
                      ->orWhere('user2_id', $userId);
            })
            ->where('status', 'accepted')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        foreach ($matches as $match) {
            $otherUserId = $match->user1_id == $userId ? $match->user2_id : $match->user1_id;
            $otherUser = DB::table('users')->find($otherUserId);

            if ($otherUser) {
                $activities[] = [
                    'type' => 'match',
                    'user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'avatar_url' => $otherUser->avatar_url ?? null,
                    ],
                    'timestamp' => $match->created_at,
                    'match_score' => (int) ($match->match_score ?? 0),
                ];
            }
        }

        // Recent messages
        $messages = DB::table('messages')
            ->where('receiver_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        foreach ($messages as $message) {
            $sender = DB::table('users')->find($message->sender_id);

            if ($sender) {
                $activities[] = [
                    'type' => 'message',
                    'user' => [
                        'id' => $sender->id,
                        'name' => $sender->name,
                        'avatar_url' => $sender->avatar_url ?? null,
                    ],
                    'timestamp' => $message->sent_at ?? $message->created_at,
                ];
            }
        }

        // Recent profile views
        $views = DB::table('profile_views')
            ->where('viewed_user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        foreach ($views as $view) {
            if ($view->viewer_user_id) {
                $viewer = DB::table('users')->find($view->viewer_user_id);

                if ($viewer) {
                    $activities[] = [
                        'type' => 'view',
                        'user' => [
                            'id' => $viewer->id,
                            'name' => $viewer->name,
                            'avatar_url' => $viewer->avatar_url ?? null,
                        ],
                        'timestamp' => $view->created_at,
                    ];
                }
            }
        }

        // Sort all activities by timestamp
        usort($activities, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        // Return top activities
        return response()->json(array_slice($activities, 0, $limit));
    }

    /**
     * @OA\Get(
     *     path="/profile/completeness",
     *     tags={"Dashboard"},
     *     summary="Get profile completeness",
     *     description="Calculate profile completion percentage and identify missing required/optional fields for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profile completeness calculated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="percentage", type="integer", minimum=0, maximum=100, example=85),
     *             @OA\Property(property="required_complete", type="boolean", example=true),
     *             @OA\Property(
     *                 property="missing_required",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 example={"city"}
     *             ),
     *             @OA\Property(
     *                 property="missing_optional",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 example={"bio", "interests", "height_cm"}
     *             ),
     *             @OA\Property(
     *                 property="sections",
     *                 type="object",
     *                 @OA\Property(property="basic", type="boolean", example=true),
     *                 @OA\Property(property="location", type="boolean", example=false),
     *                 @OA\Property(property="preferences", type="boolean", example=true),
     *                 @OA\Property(property="interests", type="boolean", example=false),
     *                 @OA\Property(property="physical", type="boolean", example=true),
     *                 @OA\Property(property="lifestyle", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     */
    public function getProfileCompleteness(Request $request)
    {
        $user = $request->user();
        
        // Required fields
        $requiredFields = [
            'name', 'email', 'age', 'gender', 'interested_in', 
            'city', 'country', 'looking_for'
        ];

        // Optional fields
        $optionalFields = [
            'bio', 'interests', 'occupation', 'education', 'height_cm',
            'body_type', 'ethnicity', 'hair_color', 'eye_color',
            'smoking', 'drinking', 'exercise', 'relationship_status',
            'has_children', 'wants_children', 'avatar_url'
        ];

        $missingRequired = [];
        $missingOptional = [];
        $filledCount = 0;
        $totalFields = count($requiredFields) + count($optionalFields);

        // Check required fields
        foreach ($requiredFields as $field) {
            $value = $user->$field;
            if (empty($value) || (is_array($value) && count($value) === 0)) {
                $missingRequired[] = $field;
            } else {
                $filledCount++;
            }
        }

        // Check optional fields
        foreach ($optionalFields as $field) {
            $value = $user->$field;
            if (empty($value) || (is_array($value) && count($value) === 0)) {
                $missingOptional[] = $field;
            } else {
                $filledCount++;
            }
        }

        $percentage = round(($filledCount / $totalFields) * 100);
        $requiredComplete = count($missingRequired) === 0;

        // Section completeness
        $sections = [
            'basic' => !empty($user->name) && !empty($user->age) && !empty($user->gender),
            'location' => !empty($user->city) && !empty($user->country),
            'preferences' => !empty($user->interested_in) && !empty($user->looking_for),
            'interests' => !empty($user->interests) && is_array($user->interests) && count($user->interests) > 0,
            'physical' => !empty($user->height_cm) || !empty($user->body_type) || !empty($user->ethnicity),
            'lifestyle' => !empty($user->smoking) || !empty($user->drinking) || !empty($user->exercise),
        ];

        return response()->json([
            'percentage' => $percentage,
            'required_complete' => $requiredComplete,
            'missing_required' => $missingRequired,
            'missing_optional' => $missingOptional,
            'sections' => $sections,
        ]);
    }
}
