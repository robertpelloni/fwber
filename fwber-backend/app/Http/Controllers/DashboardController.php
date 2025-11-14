<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics for the authenticated user
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
     * Get recent activity for the authenticated user
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
     * Get profile completeness for the authenticated user
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
