<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewFlagRequest;
use App\Http\Requests\ReviewSpoofRequest;
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
     *
     * @OA\Get(
     *   path="/moderation/dashboard",
     *   tags={"Moderation"},
     *   summary="Moderation dashboard overview",
     *   security={{"bearerAuth":{}}},
    *   @OA\Response(response=200, description="Dashboard stats and recent actions"),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
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
     *
     * @OA\Get(
     *   path="/moderation/flagged",
     *   tags={"Moderation"},
     *   summary="Flagged content queue",
     *   security={{"bearerAuth":{}}},
    *   @OA\Response(response=200, description="Paginated flagged content"),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
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

        // Transform to match test-expected shape
        $flaggedArtifacts->getCollection()->transform(function (ProximityArtifact $artifact) {
            return [
                'id' => $artifact->id,
                'user_id' => $artifact->user_id,
                'artifact_type' => $artifact->type,
                'content' => $artifact->content,
                'is_flagged' => (bool) $artifact->is_flagged,
                'flag_count' => (int) $artifact->flag_count,
            ];
        });

        return response()->json($flaggedArtifacts);
    }

    /**
     * Review and take action on flagged content.
     *
     * @OA\Post(
     *   path="/moderation/flags/{artifactId}/review",
     *   tags={"Moderation"},
     *   summary="Review flagged content",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="artifactId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"action","reason"},
     *     @OA\Property(property="action", type="string", enum={"approve","remove","throttle_user","ban_user"}),
     *     @OA\Property(property="reason", type="string", maxLength=500),
     *     @OA\Property(property="throttle_severity", type="integer", minimum=1, maximum=5),
     *     @OA\Property(property="throttle_duration_hours", type="integer", minimum=1)
     *   )),
    *   @OA\Response(response=200, description="Action completed"),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
    *   @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function reviewFlag(ReviewFlagRequest $request, int $artifactId)
    {
        if (!$request->user()->is_moderator) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validated();

        $artifact = ProximityArtifact::findOrFail($artifactId);

        DB::transaction(function () use ($artifact, $validated, $request) {
            switch ($validated['action']) {
                case 'dismiss':
                    // Dismiss the flag, keep content
                    $artifact->update([
                        'is_flagged' => false,
                        'flag_count' => 0,
                        'moderation_status' => 'clean',
                    ]);

                    ModerationAction::create([
                        'moderator_id' => $request->user()->id,
                        'target_artifact_id' => $artifact->id,
                        'target_user_id' => $artifact->user_id,
                        'action_type' => 'flag_dismissed',
                        'reason' => $validated['reason'],
                    ]);
                    break;

                case 'remove':
                    $artifact->delete();
                    
                    ModerationAction::create([
                        'moderator_id' => $request->user()->id,
                        'target_artifact_id' => $artifact->id,
                        'target_user_id' => $artifact->user_id,
                        'action_type' => 'artifact_removed',
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
     *
     * @OA\Get(
     *   path="/moderation/spoof-detections",
     *   tags={"Moderation"},
     *   summary="Geo-spoof detections",
     *   security={{"bearerAuth":{}}},
    *   @OA\Response(response=200, description="Paginated detections"),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
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
     *
     * @OA\Post(
     *   path="/moderation/spoof-detections/{detectionId}/review",
     *   tags={"Moderation"},
     *   summary="Review geo-spoof detection",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="detectionId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"action","reason"},
     *     @OA\Property(property="action", type="string", enum={"confirm","dismiss"}),
     *     @OA\Property(property="reason", type="string", maxLength=500),
     *     @OA\Property(property="apply_throttle", type="boolean")
     *   )),
    *   @OA\Response(response=200, description="Review completed"),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
    *   @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function reviewSpoof(ReviewSpoofRequest $request, int $detectionId)
    {
        if (!$request->user()->is_moderator) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validated();

        $detection = GeoSpoofDetection::findOrFail($detectionId);

        DB::transaction(function () use ($detection, $validated, $request) {
            if ($validated['action'] === 'confirm') {
                $detection->update(['is_confirmed_spoof' => true]);

                if (($validated['apply_throttle'] ?? false) === true) {
                    $severity = (int)($validated['throttle_severity'] ?? 4);
                    $this->shadowThrottleService->applyThrottle(
                        $detection->user_id,
                        'geo_spoof',
                        $severity,
                        72,
                        $request->user()->id,
                        "Geo-spoofing confirmed: " . $validated['reason']
                    );
                }

                ModerationAction::create([
                    'moderator_id' => $request->user()->id,
                    'target_user_id' => $detection->user_id,
                    'action_type' => 'spoof_confirmed',
                    'reason' => $validated['reason'],
                    'metadata' => [
                        'detection_id' => $detection->id,
                        'suspicion_score' => $detection->suspicion_score,
                        'throttle_applied' => $validated['apply_throttle'] ?? false,
                    ],
                ]);
            } else {
                // Dismiss: remove the detection entry
                $detectionId = $detection->id;
                $detection->delete();

                ModerationAction::create([
                    'moderator_id' => $request->user()->id,
                    'action_type' => 'flag_dismissed',
                    'reason' => $validated['reason'],
                    'metadata' => ['detection_id' => $detectionId],
                ]);
            }
        });

        return response()->json(['message' => 'Review completed']);
    }

    /**
     * Get active shadow throttles.
     *
     * @OA\Get(
     *   path="/moderation/throttles",
     *   tags={"Moderation"},
     *   summary="Active shadow throttles",
     *   security={{"bearerAuth":{}}},
    *   @OA\Response(response=200, description="Paginated throttles"),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
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
     *
     * @OA\Delete(
     *   path="/moderation/throttles/{throttleId}",
     *   tags={"Moderation"},
     *   summary="Remove shadow throttle",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="throttleId", in="path", required=true, @OA\Schema(type="integer")),
    *   @OA\Response(response=200, description="Throttle removed"),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
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
            'action_type' => 'throttle_removed',
            'reason' => 'Throttle removed by moderator',
            'metadata' => ['throttle_id' => $throttleId, 'action' => 'removed'],
        ]);

        return response()->json(['message' => 'Throttle removed']);
    }

    /**
     * Get moderation action history.
     *
     * @OA\Get(
     *   path="/moderation/actions",
     *   tags={"Moderation"},
     *   summary="Moderation action history",
     *   security={{"bearerAuth":{}}},
    *   @OA\Response(response=200, description="Paginated actions"),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
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
     *
     * @OA\Get(
     *   path="/moderation/users/{userId}",
     *   tags={"Moderation"},
     *   summary="User moderation profile",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="userId", in="path", required=true, @OA\Schema(type="integer")),
    *   @OA\Response(response=200, description="User moderation profile"),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function userProfile(Request $request, int $userId)
    {
        if (!$request->user()->is_moderator) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($userId);

        $moderationStats = [
            'total_flags_received' => (int) ProximityArtifact::where('user_id', $userId)->sum('flag_count'),
            'active_throttles' => (int) ShadowThrottle::where('user_id', $userId)->active()->count(),
            'confirmed_spoofs' => (int) GeoSpoofDetection::where('user_id', $userId)->where('is_confirmed_spoof', true)->count(),
            'moderation_actions' => (int) ModerationAction::where('target_user_id', $userId)->count(),
        ];

        $profile = [
            'user' => $user->only(['id', 'email', 'created_at']),
            'moderation_stats' => $moderationStats,
            // Keep additional helpful context (not asserted by tests)
            'throttle_stats' => $this->shadowThrottleService->getUserThrottleStats($userId),
            'active_throttles' => ShadowThrottle::where('user_id', $userId)->active()->get(),
            'recent_spoof_detections' => GeoSpoofDetection::where('user_id', $userId)
                ->orderBy('detected_at', 'desc')
                ->limit(10)
                ->get(),
            'recent_moderation_actions' => ModerationAction::where('target_user_id', $userId)
                ->with('moderator')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get(),
        ];

        return response()->json($profile);
    }
}
