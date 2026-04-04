<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function getStats(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;

        // Get match statistics from squashed table
        $matchStats = DB::table('user_matches')
            ->where(function ($query) use ($userId) {
                $query->where('user1_id', $userId)
                    ->orWhere('user2_id', $userId);
            })
            ->selectRaw('
                COUNT(*) as total_matches,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_matches,
                AVG(match_score) as match_score_avg
            ')
            ->first();

        // Get conversation count (unique pairs)
        $conversationCount = DB::table('messages')
            ->where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->selectRaw('
                CASE WHEN sender_id < receiver_id THEN sender_id ELSE receiver_id END as p1,
                CASE WHEN sender_id < receiver_id THEN receiver_id ELSE sender_id END as p2
            ')
            ->distinct()
            ->get()
            ->count();

        // `profile_views` was part of broader product eras and may be absent on simplified or drifted
        // production databases. Dashboard stats should degrade to zero instead of throwing a 500.
        $profileViews = 0;
        $todayViews = 0;

        if (Schema::hasTable('profile_views')) {
            $profileViews = DB::table('profile_views')
                ->where('viewed_user_id', $userId)
                ->count();

            $todayViews = DB::table('profile_views')
                ->where('viewed_user_id', $userId)
                ->whereDate('created_at', Carbon::today())
                ->count();
        }

        // Calculate response rate
        $sentMessages = DB::table('messages')->where('sender_id', $userId)->count();
        $receivedMessages = DB::table('messages')->where('receiver_id', $userId)->count();

        $responseRate = $receivedMessages > 0
            ? round(($sentMessages / $receivedMessages) * 100)
            : 0;

        $daysActive = Carbon::parse($user->created_at)->diffInDays(Carbon::now());

        return response()->json([
            'total_matches' => (int) ($matchStats->total_matches ?? 0),
            'pending_matches' => 0, // Legacy support
            'accepted_matches' => (int) ($matchStats->active_matches ?? 0),
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
     * Get recent activity feed.
     */
    public function getActivity(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;
        $limit = $request->input('limit', 10);

        $activities = [];

        // Recent matches
        $matches = DB::table('user_matches')
            ->where(function ($query) use ($userId) {
                $query->where('user1_id', $userId)
                    ->orWhere('user2_id', $userId);
            })
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
                    'timestamp' => $message->created_at,
                ];
            }
        }

        usort($activities, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return response()->json(array_slice($activities, 0, $limit));
    }

    /**
     * Get profile completeness.
     */
    public function getProfileCompleteness(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json(['percentage' => 0, 'required_complete' => false]);
        }

        $fields = [
            'display_name', 'bio', 'birthdate', 'gender', 'interests', 'latitude', 'longitude'
        ];

        $filled = 0;
        foreach ($fields as $field) {
            if (!empty($profile->$field)) $filled++;
        }

        $percentage = round(($filled / count($fields)) * 100);

        return response()->json([
            'percentage' => $percentage,
            'required_complete' => $percentage > 80,
            'missing_required' => [],
            'missing_optional' => [],
            'sections' => [],
        ]);
    }
}
