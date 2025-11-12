<?php

namespace App\Http\Controllers;

use App\Models\ModerationAction;
use App\Models\ShadowThrottle;
use App\Models\GeoSpoofDetection;
use App\Models\ProximityArtifact;
use App\Models\User;
use App\Services\ShadowThrottleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModerationController extends Controller
{
    public function __construct(
        private ShadowThrottleService $shadowThrottleService
    ) {}

    /**
     * Get moderation dashboard overview.
     */
    public function dashboard(Request $request)
    {
        // Check if user is moderator
        if (!$request->user()->is_moderator) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = [
            'flagged_artifacts' => ProximityArtifact::where('is_flagged', true)->count(),
            'active_throttles' => ShadowThrottle::active()->count(),
            'pending_spoof_detections' => GeoSpoofDetection::unconfirmed()->highRisk()->count(),
            'moderation_actions_today' => ModerationAction::whereDate('created_at', today())->count(),
        ];

        $recentActions = ModerationAction::with(['moderator', 'targetUser', 'targetArtifact'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'stats' => $stats,
            'recent_actions' => $recentActions,
        ]);
    }

    /**
     * Get flagged content for review.
     */
    public function flaggedContent(Request $request)
    {
        if (!$request->user()->is_moderator) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $flaggedArtifacts = ProximityArtifact::where('is_flagged', true)
            ->with('user')
            ->orderBy('flag_count', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($flaggedArtifacts);
    }

    /**
     * Review and take action on flagged content.
     */
    public function reviewFlag(Request $request, int $artifactId)
    {
        if (!$request->user()->is_moderator) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'action' => 'required|in:approve,remove,throttle_user,ban_user',
            'reason' => 'required|string|max:500',
            'throttle_severity' => 'nullable|integer|min:1|max:5',
            'throttle_duration_hours' => 'nullable|integer|min:1',
        ]);

        $artifact = ProximityArtifact::findOrFail($artifactId);

        DB::transaction(function () use ($artifact, $validated, $request) {
            switch ($validated['action']) {
                case 'approve':
                    // Clear flags
                    $artifact->update([
                        'is_flagged' => false,
                        'flag_count' => 0,
                    ]);
                    
                    ModerationAction::create([
                        'moderator_id' => $request->user()->id,
                        'target_artifact_id' => $artifact->id,
                        'action_type' => 'flag_review',
                        'reason' => $validated['reason'],
                        'metadata' => ['decision' => 'approved'],
                    ]);
                    break;

                case 'remove':
                    $artifact->delete();
                    
                    ModerationAction::create([
                        'moderator_id' => $request->user()->id,
                        'target_artifact_id' => $artifact->id,
                        'target_user_id' => $artifact->user_id,
                        'action_type' => 'content_removal',
                        'reason' => $validated['reason'],
                    ]);
                    break;

                case 'throttle_user':
                    $severity = $validated['throttle_severity'] ?? 2;
                    $duration = $validated['throttle_duration_hours'] ?? 24;
                    
                    $this->shadowThrottleService->applyThrottle(
                        $artifact->user_id,
                        'flagged_content',
                        $severity,
                        $duration,
                        $request->user()->id,
                        $validated['reason']
                    );
                    
                    $artifact->delete();
                    
                    ModerationAction::create([
                        'moderator_id' => $request->user()->id,
                        'target_artifact_id' => $artifact->id,
                        'target_user_id' => $artifact->user_id,
                        'action_type' => 'shadow_throttle',
                        'reason' => $validated['reason'],
                        'metadata' => [
                            'severity' => $severity,
                            'duration_hours' => $duration,
                        ],
                    ]);
                    break;

                case 'ban_user':
                    // Permanent throttle (no expiry)
                    $this->shadowThrottleService->applyThrottle(
                        $artifact->user_id,
                        'manual',
                        5,
                        null,
                        $request->user()->id,
                        "Banned: " . $validated['reason']
                    );
                    
                    ModerationAction::create([
                        'moderator_id' => $request->user()->id,
                        'target_user_id' => $artifact->user_id,
                        'action_type' => 'account_ban',
                        'reason' => $validated['reason'],
                    ]);
                    break;
            }
        });

        return response()->json(['message' => 'Action completed successfully']);
    }

    /**
     * Get suspicious geo-spoof detections.
     */
    public function spoofDetections(Request $request)
    {
        if (!$request->user()->is_moderator) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $detections = GeoSpoofDetection::with('user')
            ->unconfirmed()
            ->where('suspicion_score', '>=', 50)
            ->orderBy('suspicion_score', 'desc')
            ->orderBy('detected_at', 'desc')
            ->paginate(20);

        return response()->json($detections);
    }

    /**
     * Review geo-spoof detection.
     */
    public function reviewSpoof(Request $request, int $detectionId)
    {
        if (!$request->user()->is_moderator) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'action' => 'required|in:confirm,dismiss',
            'reason' => 'required|string|max:500',
            'apply_throttle' => 'nullable|boolean',
        ]);

        $detection = GeoSpoofDetection::findOrFail($detectionId);

        DB::transaction(function () use ($detection, $validated, $request) {
            if ($validated['action'] === 'confirm') {
                $detection->update(['is_confirmed_spoof' => true]);

                if ($validated['apply_throttle'] ?? false) {
                    $this->shadowThrottleService->applyThrottle(
                        $detection->user_id,
                        'geo_spoof',
                        3,
                        72,
                        $request->user()->id,
                        "Geo-spoofing confirmed: " . $validated['reason']
                    );
                }

                ModerationAction::create([
                    'moderator_id' => $request->user()->id,
                    'target_user_id' => $detection->user_id,
                    'action_type' => 'geo_spoof_confirm',
                    'reason' => $validated['reason'],
                    'metadata' => [
                        'detection_id' => $detection->id,
                        'suspicion_score' => $detection->suspicion_score,
                        'throttle_applied' => $validated['apply_throttle'] ?? false,
                    ],
                ]);
            } else {
                // Dismiss
                $detection->update(['suspicion_score' => 0]);

                ModerationAction::create([
                    'moderator_id' => $request->user()->id,
                    'action_type' => 'geo_spoof_dismiss',
                    'reason' => $validated['reason'],
                    'metadata' => ['detection_id' => $detection->id],
                ]);
            }
        });

        return response()->json(['message' => 'Review completed']);
    }

    /**
     * Get active shadow throttles.
     */
    public function activeThrottles(Request $request)
    {
        if (!$request->user()->is_moderator) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $throttles = ShadowThrottle::with(['user', 'creator'])
            ->active()
            ->orderBy('severity', 'desc')
            ->orderBy('started_at', 'desc')
            ->paginate(20);

        return response()->json($throttles);
    }

    /**
     * Remove shadow throttle.
     */
    public function removeThrottle(Request $request, int $throttleId)
    {
        if (!$request->user()->is_moderator) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $throttle = ShadowThrottle::findOrFail($throttleId);
        $throttle->delete();

        ModerationAction::create([
            'moderator_id' => $request->user()->id,
            'target_user_id' => $throttle->user_id,
            'action_type' => 'shadow_throttle',
            'reason' => 'Throttle removed by moderator',
            'metadata' => ['throttle_id' => $throttleId, 'action' => 'removed'],
        ]);

        return response()->json(['message' => 'Throttle removed']);
    }

    /**
     * Get moderation action history.
     */
    public function actionHistory(Request $request)
    {
        if (!$request->user()->is_moderator) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $actions = ModerationAction::with(['moderator', 'targetUser', 'targetArtifact'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($actions);
    }

    /**
     * Get user moderation profile.
     */
    public function userProfile(Request $request, int $userId)
    {
        if (!$request->user()->is_moderator) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($userId);

        $profile = [
            'user' => $user->only(['id', 'email', 'created_at']),
            'throttle_stats' => $this->shadowThrottleService->getUserThrottleStats($userId),
            'active_throttles' => ShadowThrottle::where('user_id', $userId)->active()->get(),
            'flagged_artifacts' => ProximityArtifact::where('user_id', $userId)
                ->where('is_flagged', true)
                ->count(),
            'total_artifacts' => ProximityArtifact::where('user_id', $userId)->count(),
            'spoof_detections' => GeoSpoofDetection::where('user_id', $userId)
                ->orderBy('detected_at', 'desc')
                ->limit(10)
                ->get(),
            'moderation_actions' => ModerationAction::where('target_user_id', $userId)
                ->with('moderator')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get(),
        ];

        return response()->json($profile);
    }
}
