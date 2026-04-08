<?php

use App\Http\Controllers\BlockController;
use App\Http\Controllers\BulletinBoardController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Federation (ActivityPub)
Route::prefix('federation')->group(function () {
    Route::get('search', [\App\Http\Controllers\ActivityPubSearchController::class, 'search']);
    Route::post('follow', [\App\Http\Controllers\ActivityPubSearchController::class, 'follow'])->middleware('auth:sanctum');
    Route::get('following', [\App\Http\Controllers\ActivityPubSearchController::class, 'getFollowing'])->middleware('auth:sanctum');
    Route::get('followers', [\App\Http\Controllers\ActivityPubSearchController::class, 'getFollowers'])->middleware('auth:sanctum');
    Route::get('posts', [\App\Http\Controllers\ActivityPubSearchController::class, 'getPosts']);
    Route::get('actors/detail', [\App\Http\Controllers\ActivityPubController::class, 'actorDetail']);
    Route::post('users/{id}/inbox', [\App\Http\Controllers\ActivityPubInboxController::class, 'handle'])
        ->middleware('verify.activitypub-signature');
    Route::get('users/{id}/outbox', [\App\Http\Controllers\ActivityPubOutboxController::class, 'index']);

    // Return Actor profile
    Route::get('users/{id}', [\App\Http\Controllers\ActivityPubController::class, 'actor']);
    Route::get('groups/{id}', [\App\Http\Controllers\ActivityPubController::class, 'group']);
});

Route::post('auth/register', [\App\Http\Controllers\AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('auth/login', [\App\Http\Controllers\AuthController::class, 'login'])->middleware('throttle:auth');
Route::get('auth/login', function () {
    return response()->json(['message' => 'Unauthenticated. Please login.'], 401);
})->name('login');
Route::get('auth/referral/{code}', [\App\Http\Controllers\AuthController::class, 'checkReferralCode']);
Route::post('public/vouch', [\App\Http\Controllers\VouchController::class, 'store'])->middleware('rate_limit_advanced:api_call');
Route::post('vouch/generate-link', [\App\Http\Controllers\VouchController::class, 'generateLink'])->middleware('auth:sanctum');

Route::post('auth/login-wallet', [\App\Http\Controllers\AuthController::class, 'loginWithWallet'])->middleware('throttle:auth');
Route::post('auth/two-factor-challenge', [\App\Http\Controllers\TwoFactorChallengeController::class, 'store'])->middleware('throttle:auth');

Route::post('telemetry/client-events', [\App\Http\Controllers\TelemetryController::class, 'storeClientEvents']);
Route::post('analytics/events', [\App\Http\Controllers\AnalyticsController::class, 'store']);

// Public Feature Flags (No Auth)
Route::get('config/features', function () {
    $featureFlagService = app(\App\Services\FeatureFlagService::class);

    return response()->json([
        'features' => [
            'groups' => $featureFlagService->isEnabled('groups'),
            'photos' => $featureFlagService->isEnabled('photos'),
            'proximity_artifacts' => $featureFlagService->isEnabled('proximity_artifacts'),
            'chatrooms' => $featureFlagService->isEnabled('chatrooms'),
            'proximity_chatrooms' => $featureFlagService->isEnabled('proximity_chatrooms'),
            'face_reveal' => $featureFlagService->isEnabled('face_reveal'),
            'local_media_vault' => $featureFlagService->isEnabled('local_media_vault'),
            'moderation' => $featureFlagService->isEnabled('moderation'),
            'recommendations' => $featureFlagService->isEnabled('recommendations'),
            'websocket' => true,
            'content_generation' => $featureFlagService->isEnabled('content_generation'),
            'rate_limits' => true,
            'analytics' => true,
            'video_chat' => $featureFlagService->isEnabled('video_chat'),
            'ai_wingman' => $featureFlagService->isEnabled('ai_wingman'),
            'media_analysis' => $featureFlagService->isEnabled('media_analysis'),
        ],
        'source' => 'runtime',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Public Debug Route (No Auth)
if (! app()->isProduction()) {
    Route::get('debug/public', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'database' => \Illuminate\Support\Facades\DB::connection()->getDatabaseName(),
        ]);
    });

    // Debug Route to diagnose 500 errors
    Route::get('debug/user', function (Request $request) {
        try {
            $user = $request->user();

            return response()->json([
                'user' => $user,
                'profile' => $user->profile,
                'photos' => $user->photos,
                'two_factor' => $user->hasEnabledTwoFactorAuthentication(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    })->middleware('auth:sanctum');
}

// Merchant API (Public / Key Auth)
Route::post('merchant/checkout', [\App\Http\Controllers\Api\MerchantController::class, 'createPayment']);
Route::get('merchant/payment/{id}', [\App\Http\Controllers\Api\MerchantController::class, 'show']);

// Health Checks
Route::group(['middleware' => ['edge.cache:30,15']], function () {
    Route::get('health', [\App\Http\Controllers\HealthController::class, 'check']);
    Route::get('health/liveness', [\App\Http\Controllers\HealthController::class, 'liveness']);
    Route::get('health/readiness', [\App\Http\Controllers\HealthController::class, 'readiness']);
    Route::get('health/metrics', [\App\Http\Controllers\HealthController::class, 'metrics']);
});

// Stripe Webhook
Route::post('stripe/webhook', [\App\Http\Controllers\StripeWebhookController::class, 'handleWebhook']);

// Public Viral Content
Route::get('viral-content/{id}', [\App\Http\Controllers\ViralContentController::class, 'show']);

// Public Roast Generator (Rate Limited strictly)
Route::post('public/roast', [\App\Http\Controllers\AiWingmanController::class, 'roastPublic'])
    ->middleware('rate_limit_advanced:content_generation'); // Use advanced token bucket limiter

// Public Matchmaker Bounty
Route::get('matchmaker/bounty/{slug}', [\App\Http\Controllers\MatchMakerController::class, 'showBounty']);

// Physical Pulse (IoT Nodes)
Route::get('public/pulse/node/{uuid}', [\App\Http\Controllers\PhysicalPulseController::class, 'node']);

// Secret Diagnostics
Route::get('diagnostics/logs', [\App\Http\Controllers\DiagnosticsController::class, 'logs']);

// Venue Partner Auth
Route::prefix('venue')->group(function () {
    Route::post('register', [\App\Http\Controllers\VenueAuthController::class, 'register']);
    Route::post('login', [\App\Http\Controllers\VenueAuthController::class, 'login']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('logout', [\App\Http\Controllers\VenueAuthController::class, 'logout']);
        Route::get('me', [\App\Http\Controllers\VenueAuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    // Device Token routes for Push Notifications
    Route::prefix('device-tokens')->group(function (): void {
        Route::post('/', [DeviceTokenController::class, 'store']);
        Route::delete('/{token}', [DeviceTokenController::class, 'destroy']);
    });
    Route::get('device-tokens', [DeviceTokenController::class, 'index']);

    // Block routes
    Route::prefix('blocks')->group(function (): void {
        Route::post('/', [BlockController::class, 'store']);
        Route::delete('/{blockedId}', [BlockController::class, 'destroy']);
        Route::get('/', [BlockController::class, 'index']);
    });

    // Report routes
    Route::prefix('reports')->group(function (): void {
        Route::post('/', [ReportController::class, 'store']);
        Route::get('/', [ReportController::class, 'index']);
        Route::put('/{reportId}', [ReportController::class, 'update']);
    });

    // Auth
    Route::post('auth/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
    Route::get('auth/me', [\App\Http\Controllers\AuthController::class, 'me']);

    // Two Factor Authentication
    Route::post('user/two-factor-authentication', [\App\Http\Controllers\TwoFactorAuthenticationController::class, 'store']);
    Route::post('user/confirmed-two-factor-authentication', [\App\Http\Controllers\TwoFactorAuthenticationController::class, 'confirm']);
    Route::delete('user/two-factor-authentication', [\App\Http\Controllers\TwoFactorAuthenticationController::class, 'destroy']);
    Route::get('user/two-factor-qr-code', [\App\Http\Controllers\TwoFactorAuthenticationController::class, 'showQrCode']);
    Route::get('user/two-factor-recovery-codes', [\App\Http\Controllers\TwoFactorAuthenticationController::class, 'recoveryCodes']);
    Route::post('user/two-factor-recovery-codes', [\App\Http\Controllers\TwoFactorAuthenticationController::class, 'regenerateRecoveryCodes']);

    // E2E Encryption Keys
    Route::prefix('security/keys')->group(function () {
        Route::post('/', [\App\Http\Controllers\E2EKeyManagementController::class, 'store']);
        Route::get('/me', [\App\Http\Controllers\E2EKeyManagementController::class, 'me']);
        Route::get('/{userId}', [\App\Http\Controllers\E2EKeyManagementController::class, 'show']);
    });

    // Profile
    Route::get('users/{id}', [\App\Http\Controllers\ProfileController::class, 'showPublic']);
    Route::get('profile', [\App\Http\Controllers\ProfileController::class, 'show']);
    Route::put('profile', [\App\Http\Controllers\ProfileController::class, 'update']);
    Route::patch('profile/emotion', [\App\Http\Controllers\AvatarEmotionController::class, 'update']);
    Route::put('profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword']);
    Route::delete('profile', [\App\Http\Controllers\ProfileController::class, 'destroy']);
    Route::get('profile/completeness', [\App\Http\Controllers\ProfileController::class, 'completeness']);
    Route::get('profile/export', [\App\Http\Controllers\ProfileController::class, 'export']);

    // Security & Decoy Profile
    Route::prefix('settings')->group(function () {
        Route::post('decoy-profile', [\App\Http\Controllers\DecoyProfileController::class, 'setup']);
        Route::delete('decoy-profile', [\App\Http\Controllers\DecoyProfileController::class, 'remove']);
    });

    // GDPR Data Export
    Route::post('user/export', [\App\Http\Controllers\DataExportController::class, 'requestExport'])->middleware('throttle:1,1440'); // Once per day
    Route::get('user/export/status', [\App\Http\Controllers\DataExportController::class, 'checkStatus']);
    Route::get('user/export/{filename}', [\App\Http\Controllers\DataExportController::class, 'download'])->name('api.user.export.download');

    // Onboarding
    Route::get('onboarding/status', [\App\Http\Controllers\OnboardingController::class, 'getStatus']);
    Route::post('onboarding/complete', [\App\Http\Controllers\OnboardingController::class, 'complete']);

    // Physical Profile
    Route::get('physical-profile', [\App\Http\Controllers\UserPhysicalProfileController::class, 'show']);
    Route::put('physical-profile', [\App\Http\Controllers\UserPhysicalProfileController::class, 'upsert']);
    Route::post('physical-profile/avatar/request', [\App\Http\Controllers\UserPhysicalProfileController::class, 'requestAvatar']);

    // Dashboard
    Route::get('dashboard/stats', [\App\Http\Controllers\DashboardController::class, 'getStats']);
    Route::get('dashboard/activity', [\App\Http\Controllers\DashboardController::class, 'getActivity']);
    Route::get('dashboard/completeness', [\App\Http\Controllers\DashboardController::class, 'getProfileCompleteness']);

    // WebSocket
    Route::get('websocket/token', [\App\Http\Controllers\WebSocketController::class, 'getToken']);
    Route::post('websocket/connect', [\App\Http\Controllers\WebSocketController::class, 'connect']);
    Route::post('websocket/disconnect', [\App\Http\Controllers\WebSocketController::class, 'disconnect']);
    Route::post('websocket/message', [\App\Http\Controllers\WebSocketController::class, 'sendMessage']);
    Route::post('websocket/typing', [\App\Http\Controllers\WebSocketController::class, 'sendTypingIndicator']);
    Route::post('websocket/presence', [\App\Http\Controllers\WebSocketController::class, 'updatePresence']);
    Route::post('websocket/notification', [\App\Http\Controllers\WebSocketController::class, 'sendNotification']);
    Route::get('websocket/online-users', [\App\Http\Controllers\WebSocketController::class, 'getOnlineUsers']);
    Route::get('websocket/connections', [\App\Http\Controllers\WebSocketController::class, 'getUserConnections']);
    Route::get('websocket/status', [\App\Http\Controllers\WebSocketController::class, 'status']);
    Route::post('websocket/broadcast', [\App\Http\Controllers\WebSocketController::class, 'broadcast']);

    // Pusher Auth
    Route::post('broadcasting/auth', function (Request $request) {
        return Broadcast::auth($request);
    });

    // Video Chat
    Route::middleware('feature:video_chat')->group(function () {
        Route::post('video/signal', [\App\Http\Controllers\VideoChatController::class, 'signal']);
        Route::post('video/initiate', [\App\Http\Controllers\VideoChatController::class, 'initiate']);
        Route::put('video/{id}/status', [\App\Http\Controllers\VideoChatController::class, 'updateStatus']);
        Route::get('video/history', [\App\Http\Controllers\VideoChatController::class, 'history']);
    });

    // Rate My Pussy (Viral Cat Rating)
    Route::get('cats', [\App\Http\Controllers\CatPhotoController::class, 'index'])->withoutMiddleware(['auth:sanctum']);
    Route::post('cats', [\App\Http\Controllers\CatPhotoController::class, 'store']);
    Route::post('cats/{id}/rate', [\App\Http\Controllers\CatPhotoController::class, 'rate']);

    // Analytics
    Route::get('analytics', [\App\Http\Controllers\AnalyticsController::class, 'index']);
    Route::get('analytics/realtime', [\App\Http\Controllers\AnalyticsController::class, 'realtime']);
    Route::get('analytics/moderation', [\App\Http\Controllers\AnalyticsController::class, 'moderation']);
    Route::get('analytics/slow-requests', [\App\Http\Controllers\AnalyticsController::class, 'slowRequests']);
    Route::get('analytics/slow-requests/stats', [\App\Http\Controllers\AnalyticsController::class, 'slowRequestStats']);
    Route::get('analytics/slow-requests/analysis', [\App\Http\Controllers\AnalyticsController::class, 'analyzeSlowRequests']);
    Route::get('analytics/boosts', [\App\Http\Controllers\AnalyticsController::class, 'boosts']);
    Route::get('analytics/retention', [\App\Http\Controllers\AnalyticsController::class, 'retention']);

    // Logs (Admin)
    Route::get('admin/logs', [\App\Http\Controllers\LogController::class, 'index']);
    Route::get('admin/logs/{filename}', [\App\Http\Controllers\LogController::class, 'show']);

    // Failed Jobs (Admin)
    Route::get('analytics/failed-jobs', [\App\Http\Controllers\FailedJobController::class, 'index']);
    Route::post('analytics/failed-jobs/retry-all', [\App\Http\Controllers\FailedJobController::class, 'retryAll']);
    Route::post('analytics/failed-jobs/flush', [\App\Http\Controllers\FailedJobController::class, 'flush']);
    Route::post('analytics/failed-jobs/{uuid}/retry', [\App\Http\Controllers\FailedJobController::class, 'retry']);
    Route::delete('analytics/failed-jobs/{uuid}', [\App\Http\Controllers\FailedJobController::class, 'destroy']);

    // Notifications
    Route::get('notification-preferences', [\App\Http\Controllers\NotificationPreferenceController::class, 'index']);
    Route::put('notification-preferences/{type}', [\App\Http\Controllers\NotificationPreferenceController::class, 'update']);
    Route::get('notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::get('notifications/count', [\App\Http\Controllers\NotificationController::class, 'count']);
    Route::post('notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
    Route::post('notifications/subscribe', [\App\Http\Controllers\NotificationController::class, 'subscribe']);
    Route::post('notifications/unsubscribe', [\App\Http\Controllers\NotificationController::class, 'unsubscribe']);

    // Events
    Route::get('events/my-events', [EventController::class, 'myEvents']);
    Route::get('events/invitations', [\App\Http\Controllers\EventInvitationController::class, 'index']);
    Route::post('events/{id}/invite', [\App\Http\Controllers\EventInvitationController::class, 'store']);
    Route::post('events/invitations/{id}/respond', [\App\Http\Controllers\EventInvitationController::class, 'respond']);
    Route::apiResource('events', EventController::class);
    Route::post('events/{id}/rsvp', [EventController::class, 'rsvp']);

    // Groups
    Route::get('groups/my-groups', [\App\Http\Controllers\GroupController::class, 'myGroups']);
    Route::post('groups/{id}/join', [\App\Http\Controllers\GroupController::class, 'join']);
    Route::post('groups/{id}/leave', [\App\Http\Controllers\GroupController::class, 'leave']);
    Route::get('groups/{id}/matches', [\App\Http\Controllers\GroupController::class, 'matches']);
    Route::get('groups/{id}/matches/requests', [\App\Http\Controllers\GroupController::class, 'matchRequests']);
    Route::get('groups/{id}/matches/connected', [\App\Http\Controllers\GroupController::class, 'connectedMatches']);
    Route::post('groups/{id}/matches/{targetGroupId}/connect', [\App\Http\Controllers\GroupController::class, 'requestMatch']);
    Route::post('groups/{id}/matches/requests/{matchId}/accept', [\App\Http\Controllers\GroupController::class, 'acceptMatchRequest']);
    Route::post('groups/{id}/matches/requests/{matchId}/reject', [\App\Http\Controllers\GroupController::class, 'rejectMatchRequest']);
    Route::get('groups/{id}/events', [\App\Http\Controllers\GroupController::class, 'events']);
    Route::apiResource('groups', \App\Http\Controllers\GroupController::class);

    // Premium
    Route::get('premium/who-likes-you', [\App\Http\Controllers\PremiumController::class, 'getWhoLikesYou']);
    Route::post('premium/initiate', [\App\Http\Controllers\PremiumController::class, 'initiatePurchase']);
    Route::post('premium/purchase', [\App\Http\Controllers\PremiumController::class, 'purchasePremium']);
    Route::get('premium/status', [\App\Http\Controllers\PremiumController::class, 'getPremiumStatus']);
    Route::get('referrals/summary', [\App\Http\Controllers\ReferralController::class, 'summary']);

    // Boosts
    Route::post('boosts/purchase', [\App\Http\Controllers\BoostController::class, 'purchaseBoost']);
    Route::get('boosts/active', [\App\Http\Controllers\BoostController::class, 'getActiveBoost']);
    Route::get('boosts/history', [\App\Http\Controllers\BoostController::class, 'getBoostHistory']);

    // Recommendations
    Route::get('recommendations', [\App\Http\Controllers\RecommendationController::class, 'index']);
    Route::get('recommendations/type/{type}', [\App\Http\Controllers\RecommendationController::class, 'byType']);
    Route::get('recommendations/trending', [\App\Http\Controllers\RecommendationController::class, 'trending']);
    Route::get('recommendations/feed', [\App\Http\Controllers\RecommendationController::class, 'feed']);
    Route::post('recommendations/feedback', [\App\Http\Controllers\RecommendationController::class, 'feedback']);
    Route::get('recommendations/analytics', [\App\Http\Controllers\RecommendationController::class, 'analytics']);

    // Proximity Artifacts
    Route::get('proximity/feed', [\App\Http\Controllers\ProximityArtifactController::class, 'index']);
    Route::post('proximity/artifacts', [\App\Http\Controllers\ProximityArtifactController::class, 'store']);
    Route::get('proximity/artifacts/{id}', [\App\Http\Controllers\ProximityArtifactController::class, 'show']);
    Route::post('proximity/artifacts/{id}/claim', [\App\Http\Controllers\ProximityArtifactController::class, 'claim']);
    Route::post('proximity/artifacts/{id}/flag', [\App\Http\Controllers\ProximityArtifactController::class, 'flag']);
    Route::get('proximity/artifacts/{artifact}/comments', [\App\Http\Controllers\Api\ProximityArtifactCommentController::class, 'index']);
    Route::post('proximity/artifacts/{artifact}/comments', [\App\Http\Controllers\Api\ProximityArtifactCommentController::class, 'store']);
    Route::delete('proximity/artifacts/comments/{comment}', [\App\Http\Controllers\Api\ProximityArtifactCommentController::class, 'destroy']);
    Route::post('proximity/artifacts/{artifact}/vote', [\App\Http\Controllers\Api\ProximityArtifactVoteController::class, 'vote']);
    Route::delete('proximity/artifacts/{id}', [\App\Http\Controllers\ProximityArtifactController::class, 'destroy']);
    Route::get('proximity/local-pulse', [\App\Http\Controllers\ProximityArtifactController::class, 'localPulse']);

    // Matches
    Route::get('matches', [\App\Http\Controllers\MatchController::class, 'index']);
    Route::get('matches/established', [\App\Http\Controllers\MatchController::class, 'establishedMatches']);
    Route::get('matches/history', [\App\Http\Controllers\MatchController::class, 'index']); // alias for frontend
    Route::get('matches/{id}/insights', [\App\Http\Controllers\MatchInsightsController::class, 'show']);
    Route::post('matches/{id}/insights/unlock', [\App\Http\Controllers\MatchInsightsController::class, 'unlock']);
    Route::get('matches/insights/unlocked', function () {
        // Return list of match insights the user has unlocked
        $user = \Illuminate\Support\Facades\Auth::user();
        $unlocked = \App\Models\ContentUnlock::where('user_id', $user->id)
            ->where('content_type', 'match_insight')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return response()->json(['data' => $unlocked->items()]);
    });
    Route::post('matches/action', [\App\Http\Controllers\MatchController::class, 'action'])->middleware('throttle:matching');
    Route::post('matches/nfc-exchange', [\App\Http\Controllers\MatchController::class, 'nfcExchange']);

    // Post-Date Feedback
    Route::post('matches/{matchId}/feedback', [\App\Http\Controllers\DateFeedbackController::class, 'store']);
    Route::get('matches/{matchId}/feedback', [\App\Http\Controllers\DateFeedbackController::class, 'show']);

    // Direct Messages
    Route::get('messages/unread-count', [\App\Http\Controllers\MessageController::class, 'unreadCount']);
    Route::post('messages/sync-batch', [\App\Http\Controllers\MessageController::class, 'syncBatch'])->middleware('throttle:messaging');
    Route::post('messages', [\App\Http\Controllers\MessageController::class, 'store'])->middleware('throttle:messaging');
    Route::post('messages/translate', [\App\Http\Controllers\TranslationController::class, 'translate'])->middleware('throttle:content_generation');
    Route::get('messages/{userId}', [\App\Http\Controllers\MessageController::class, 'index']);
    Route::post('messages/{messageId}/read', [\App\Http\Controllers\MessageController::class, 'markAsRead']);
    Route::post('messages/mark-all-read/{senderId}', [\App\Http\Controllers\MessageController::class, 'markAllAsRead']);

    // Chatrooms
    Route::post('burner-links', [\App\Http\Controllers\BurnerLinkController::class, 'store']);
    Route::post('burner-links/{token}/join', [\App\Http\Controllers\BurnerLinkController::class, 'join']);

    // ZK-Proximity
    Route::get('proximity/zk-params', [\App\Http\Controllers\ZKProximityController::class, 'params']);
    Route::post('proximity/zk-verify', [\App\Http\Controllers\ZKProximityController::class, 'verify']);

    // Identity Verification (ZK)
    Route::prefix('identity')->group(function () {
        Route::post('verify-zk', [\App\Http\Controllers\IdentityController::class, 'verify']);
        Route::get('status', [\App\Http\Controllers\IdentityController::class, 'status']);
    });

    // Audio Rooms
    Route::prefix('audio-rooms')->group(function () {
        Route::get('/', [\App\Http\Controllers\AudioRoomController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\AudioRoomController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\AudioRoomController::class, 'show']);
        Route::post('/{id}/join', [\App\Http\Controllers\AudioRoomController::class, 'join']);
        Route::post('/{id}/leave', [\App\Http\Controllers\AudioRoomController::class, 'leave']);
        Route::post('/{id}/signal', [\App\Http\Controllers\AudioRoomController::class, 'signal']);
    });

    Route::get('chatrooms/my', [\App\Http\Controllers\ChatroomController::class, 'myChatrooms']);
    Route::get('chatrooms/categories', [\App\Http\Controllers\ChatroomController::class, 'categories']);
    Route::get('chatrooms/popular', [\App\Http\Controllers\ChatroomController::class, 'popular']);
    Route::get('chatrooms/search', [\App\Http\Controllers\ChatroomController::class, 'search']);
    Route::get('chatrooms', [\App\Http\Controllers\ChatroomController::class, 'index']);
    Route::post('chatrooms', [\App\Http\Controllers\ChatroomController::class, 'store']);
    Route::get('chatrooms/{id}', [\App\Http\Controllers\ChatroomController::class, 'show']);
    Route::put('chatrooms/{id}', [\App\Http\Controllers\ChatroomController::class, 'update']);
    Route::delete('chatrooms/{id}', [\App\Http\Controllers\ChatroomController::class, 'destroy']);
    Route::post('chatrooms/{id}/join', [\App\Http\Controllers\ChatroomController::class, 'join']);
    Route::post('chatrooms/{id}/leave', [\App\Http\Controllers\ChatroomController::class, 'leave']);
    Route::get('chatrooms/{id}/members', [\App\Http\Controllers\ChatroomController::class, 'members']);

    // Chatroom Messages
    Route::get('chatrooms/{chatroomId}/messages', [\App\Http\Controllers\ChatroomMessageController::class, 'index']);
    Route::post('chatrooms/{chatroomId}/messages', [\App\Http\Controllers\ChatroomMessageController::class, 'store'])->middleware('throttle:messaging');

    // Proximity Chatrooms
    Route::get('proximity-chatrooms/nearby', [\App\Http\Controllers\ProximityChatroomController::class, 'findNearby']);
    Route::get('proximity-chatrooms/conference-pulse', [\App\Http\Controllers\ProximityChatroomController::class, 'conferencePulse']);
    Route::post('proximity-chatrooms', [\App\Http\Controllers\ProximityChatroomController::class, 'create']);
    Route::get('proximity-chatrooms/{id}', [\App\Http\Controllers\ProximityChatroomController::class, 'show']);
    Route::post('proximity-chatrooms/{id}/join', [\App\Http\Controllers\ProximityChatroomController::class, 'join']);
    Route::post('proximity-chatrooms/{id}/leave', [\App\Http\Controllers\ProximityChatroomController::class, 'leave']);
    Route::post('proximity-chatrooms/{id}/location', [\App\Http\Controllers\ProximityChatroomController::class, 'updateLocation']);
    Route::get('proximity-chatrooms/{id}/members', [\App\Http\Controllers\ProximityChatroomController::class, 'members']);
    Route::get('proximity-chatrooms/{id}/networking', [\App\Http\Controllers\ProximityChatroomController::class, 'nearbyNetworking']);
    Route::get('proximity-chatrooms/{id}/analytics', [\App\Http\Controllers\ProximityChatroomController::class, 'analytics']);

    // Proximity Chatroom Messages
    Route::get('proximity-chatrooms/{chatroomId}/messages', [\App\Http\Controllers\ProximityChatroomMessageController::class, 'index']);
    Route::post('proximity-chatrooms/{chatroomId}/messages', [\App\Http\Controllers\ProximityChatroomMessageController::class, 'store'])->middleware('throttle:messaging');
    Route::get('proximity-chatrooms/{chatroomId}/messages/pinned', [\App\Http\Controllers\ProximityChatroomMessageController::class, 'pinned']);
    Route::get('proximity-chatrooms/{chatroomId}/messages/networking', [\App\Http\Controllers\ProximityChatroomMessageController::class, 'networking']);
    Route::get('proximity-chatrooms/{chatroomId}/messages/social', [\App\Http\Controllers\ProximityChatroomMessageController::class, 'social']);
    Route::get('proximity-chatrooms/{chatroomId}/messages/{messageId}', [\App\Http\Controllers\ProximityChatroomMessageController::class, 'show']);
    Route::put('proximity-chatrooms/{chatroomId}/messages/{messageId}', [\App\Http\Controllers\ProximityChatroomMessageController::class, 'update']);
    Route::delete('proximity-chatrooms/{chatroomId}/messages/{messageId}', [\App\Http\Controllers\ProximityChatroomMessageController::class, 'destroy']);
    Route::post('proximity-chatrooms/{chatroomId}/messages/{messageId}/reactions', [\App\Http\Controllers\ProximityChatroomMessageController::class, 'addReaction']);
    Route::delete('proximity-chatrooms/{chatroomId}/messages/{messageId}/reactions', [\App\Http\Controllers\ProximityChatroomMessageController::class, 'removeReaction']);
    Route::post('proximity-chatrooms/{chatroomId}/messages/{messageId}/pin', [\App\Http\Controllers\ProximityChatroomMessageController::class, 'pin']);
    Route::delete('proximity-chatrooms/{chatroomId}/messages/{messageId}/pin', [\App\Http\Controllers\ProximityChatroomMessageController::class, 'unpin']);
    Route::get('proximity-chatrooms/{chatroomId}/messages/{messageId}/replies', [\App\Http\Controllers\ProximityChatroomMessageController::class, 'replies']);

    // Restored Resources
    Route::apiResource('venues', \App\Http\Controllers\VenueController::class);
    Route::get('subscriptions/history', [\App\Http\Controllers\SubscriptionController::class, 'history']);
    Route::post('subscriptions/cancel', [\App\Http\Controllers\SubscriptionController::class, 'cancel']);
    Route::apiResource('subscriptions', \App\Http\Controllers\SubscriptionController::class);

    // Creator Subscriptions
    Route::post('subscriptions/creator/{creatorId}', [\App\Http\Controllers\Api\CreatorSubscriptionController::class, 'subscribe']);
    Route::get('subscriptions/creator/{creatorId}', [\App\Http\Controllers\Api\CreatorSubscriptionController::class, 'check']);

    // Creator Subscriptions
    Route::post('subscriptions/creators/{creatorId}/subscribe', [\App\Http\Controllers\CreatorSubscriptionController::class, 'subscribe']);
    Route::get('subscriptions/creators/{creatorId}/check', [\App\Http\Controllers\CreatorSubscriptionController::class, 'check']);

    // Merchant Portal (Phase 4B)
    Route::prefix('merchant-portal')->group(function () {
        Route::post('register', [\App\Http\Controllers\MerchantController::class, 'register']);
        Route::get('profile', [\App\Http\Controllers\MerchantController::class, 'getProfile']);
        Route::put('profile', [\App\Http\Controllers\MerchantController::class, 'updateProfile']);
        Route::get('promotions', [\App\Http\Controllers\MerchantController::class, 'getPromotions']);
        Route::post('promotions', [\App\Http\Controllers\MerchantController::class, 'storePromotion']);
        Route::get('promotions/{promotionId}', [\App\Http\Controllers\MerchantController::class, 'showPromotion']);
        Route::put('promotions/{promotionId}', [\App\Http\Controllers\MerchantController::class, 'updatePromotion']);
        Route::delete('promotions/{promotionId}', [\App\Http\Controllers\MerchantController::class, 'destroyPromotion']);
        Route::get('analytics', [\App\Http\Controllers\MerchantAnalyticsController::class, 'index']);
        Route::get('analytics/export', [\App\Http\Controllers\MerchantAnalyticsController::class, 'exportCsv']);

        // Marketplace (Merchant Actions)
        Route::get('inventory', [\App\Http\Controllers\MerchantInventoryController::class, 'store']); // Use same for list
        Route::post('inventory', [\App\Http\Controllers\MerchantInventoryController::class, 'store']);
        Route::post('inventory/redeem', [\App\Http\Controllers\MerchantInventoryController::class, 'redeem']);
    });

    // Marketplace (User Actions)
    Route::get('marketplace/nearby', [\App\Http\Controllers\MerchantInventoryController::class, 'nearby']);
    Route::get('marketplace/{merchantId}', [\App\Http\Controllers\MerchantInventoryController::class, 'index']);
    Route::post('marketplace/purchase/{itemId}', [\App\Http\Controllers\MerchantInventoryController::class, 'purchase']);

    // Governance (Council)
    Route::get('governance/proposals', [\App\Http\Controllers\GovernanceController::class, 'index']);
    Route::post('governance/proposals', [\App\Http\Controllers\GovernanceController::class, 'store']);
    Route::post('governance/proposals/{id}/vote', [\App\Http\Controllers\GovernanceController::class, 'vote']);
    Route::get('governance/proposals/{id}/proof', [\App\Http\Controllers\GovernanceController::class, 'getVoteProof']);
    Route::post('governance/appeals', [\App\Http\Controllers\GovernanceAppealController::class, 'store']);

    // Economy & Swaps
    Route::get('economy/rates', [\App\Http\Controllers\Api\SwapController::class, 'getRates']);
    Route::get('economy/swaps', [\App\Http\Controllers\Api\SwapController::class, 'index']);
    Route::post('economy/swaps/initiate', [\App\Http\Controllers\Api\SwapController::class, 'initiate']);

    // Federated Identity (WebFinger Auth)
    Route::post('auth/federated/challenge', [\App\Http\Controllers\ActivityPubAuthController::class, 'challenge']);
    Route::post('auth/federated/verify', [\App\Http\Controllers\ActivityPubAuthController::class, 'verify']);

    // Merchant API (Authenticated)
    Route::post('merchant/keys', [\App\Http\Controllers\Api\MerchantController::class, 'generateKeys']);
    Route::post('merchant/payment/{id}/confirm', [\App\Http\Controllers\Api\MerchantController::class, 'confirm']);

    // Venue Check-ins
    Route::post('venues/{id}/checkin', [\App\Http\Controllers\VenueCheckinController::class, 'store']);
    Route::post('venues/{id}/checkout', [\App\Http\Controllers\VenueCheckinController::class, 'destroy']);
    Route::get('venues/{id}/checkins', [\App\Http\Controllers\VenueCheckinController::class, 'index']);
    Route::get('user/checkin', [\App\Http\Controllers\VenueCheckinController::class, 'current']);

    // Relationship Tiers (Face Reveal)
    Route::middleware('feature:face_reveal')->group(function () {
        Route::get('matches/{matchId}/tier', [\App\Http\Controllers\RelationshipTierController::class, 'show']);
        Route::put('matches/{matchId}/tier', [\App\Http\Controllers\RelationshipTierController::class, 'update']);
        Route::get('matches/{matchId}/tier/photos', [\App\Http\Controllers\RelationshipTierController::class, 'getPhotos']);
    });

    // AI Avatar Generation
    Route::get('avatar/providers', [\App\Http\Controllers\AvatarController::class, 'providers']);
    Route::post('avatar/generate', [\App\Http\Controllers\AvatarController::class, 'generate']);
    Route::post('avatar/generate-from-photo', [\App\Http\Controllers\AvatarController::class, 'generateFromPhoto']);
    Route::get('avatar/physical-traits', [\App\Http\Controllers\AvatarController::class, 'physicalTraits']);

    // AI Content Generation (rate limited)
    Route::middleware('throttle:content_generation')->group(function () {
        Route::post('content/generate-bio', [\App\Http\Controllers\ContentGenerationController::class, 'generateProfileContent']);
        Route::post('content/generate-posts/{boardId}', [\App\Http\Controllers\ContentGenerationController::class, 'generatePostSuggestions']);
        Route::post('content/generate-starters', [\App\Http\Controllers\ContentGenerationController::class, 'generateConversationStarters']);
    });

    // Content Generation (full suite)
    Route::prefix('content-generation')->group(function () {
        Route::post('optimize', [\App\Http\Controllers\ContentGenerationController::class, 'optimizeContent']);
        Route::get('stats', [\App\Http\Controllers\ContentGenerationController::class, 'getGenerationStats']);
        Route::get('optimization-stats', [\App\Http\Controllers\ContentGenerationController::class, 'getOptimizationStats']);
        Route::post('feedback', [\App\Http\Controllers\ContentGenerationController::class, 'submitContentFeedback']);
        Route::get('history', [\App\Http\Controllers\ContentGenerationController::class, 'getGenerationHistory']);
        Route::delete('content/{contentId}', [\App\Http\Controllers\ContentGenerationController::class, 'deleteGeneratedContent']);
    });

    // Photos (upload rate limited)
    Route::middleware('throttle:photo_uploads')->group(function () {
        Route::post('photos', [\App\Http\Controllers\PhotoController::class, 'store']);
        Route::post('photos/reorder', [\App\Http\Controllers\PhotoController::class, 'reorder']);
    });
    Route::post('photos/{id}/reveal', [\App\Http\Controllers\PhotoController::class, 'reveal']);
    Route::post('photos/{id}/unlock', [\App\Http\Controllers\PhotoController::class, 'unlock']);
    Route::get('photos/{id}/original', [\App\Http\Controllers\PhotoController::class, 'original']);
    Route::get('photos', [\App\Http\Controllers\PhotoController::class, 'index']);
    Route::get('photos/settings', function () {
        return response()->json([
            'max_photos' => 6,
            'max_file_size' => 5 * 1024 * 1024,
            'allowed_formats' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            'compression_quality' => 85,
        ]);
    });
    Route::get('photos/reveals', function () {
        // Returns list of photo reveal requests for the authenticated user
        $user = \Illuminate\Support\Facades\Auth::user();
        $reveals = \App\Models\PhotoReveal::where('target_user_id', $user->id)
            ->with(['photo', 'requester'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return response()->json([
            'data' => $reveals->items(),
            'meta' => [
                'current_page' => $reveals->currentPage(),
                'total' => $reveals->total(),
                'has_more' => $reveals->hasMorePages(),
            ],
        ]);
    });
    Route::get('photos/{id}', [\App\Http\Controllers\PhotoController::class, 'show']);
    Route::put('photos/{id}', [\App\Http\Controllers\PhotoController::class, 'update']);
    Route::delete('photos/{id}', [\App\Http\Controllers\PhotoController::class, 'destroy']);

    // Media Analysis
    Route::middleware('feature:media_analysis')->group(function () {
        Route::post('media/analyze', [\App\Http\Controllers\MediaAnalysisController::class, 'analyze']);
    });

    // Safety: Emergency Contacts & Safe Walk
    Route::get('safety/contacts', [\App\Http\Controllers\SafetyController::class, 'getContacts']);
    Route::post('safety/contacts', [\App\Http\Controllers\SafetyController::class, 'addContact']);
    Route::delete('safety/contacts/{id}', [\App\Http\Controllers\SafetyController::class, 'deleteContact']);
    Route::post('safety/panic', [\App\Http\Controllers\SafetyController::class, 'triggerPanic']);
    Route::post('safety/walk/start', [\App\Http\Controllers\SafetyController::class, 'startSafeWalk']);
    Route::patch('safety/walk/{walkId}/location', [\App\Http\Controllers\SafetyController::class, 'updateLocation']);
    Route::post('safety/walk/{walkId}/end', [\App\Http\Controllers\SafetyController::class, 'endSafeWalk']);
    Route::get('safety/walk/active', [\App\Http\Controllers\SafetyController::class, 'getActiveWalk']);

    // Scrapbook
    Route::get('scrapbook/{matchId}', [\App\Http\Controllers\ScrapbookController::class, 'index']);
    Route::post('scrapbook', [\App\Http\Controllers\ScrapbookController::class, 'store']);
    Route::patch('scrapbook/{id}/pin', [\App\Http\Controllers\ScrapbookController::class, 'togglePin']);
    Route::delete('scrapbook/{id}', [\App\Http\Controllers\ScrapbookController::class, 'destroy']);

    // Journals / Field Notes
    Route::get('journals', [\App\Http\Controllers\JournalController::class, 'index']);
    Route::post('journals', [\App\Http\Controllers\JournalController::class, 'store']);
    Route::get('journals/{journal}', [\App\Http\Controllers\JournalController::class, 'show']);
    Route::put('journals/{journal}', [\App\Http\Controllers\JournalController::class, 'update']);
    Route::delete('journals/{journal}', [\App\Http\Controllers\JournalController::class, 'destroy']);
    Route::get('settings/privacy/journals', [\App\Http\Controllers\JournalPrivacyController::class, 'show']);
    Route::put('settings/privacy/journals', [\App\Http\Controllers\JournalPrivacyController::class, 'update']);

    Route::prefix('topics')->group(function () {
        Route::get('/', [\App\Http\Controllers\TopicController::class, 'index']);
        Route::get('/followed', [\App\Http\Controllers\TopicController::class, 'followed']);
        Route::get('/{slug}', [\App\Http\Controllers\TopicController::class, 'show']);
        Route::post('/{slug}/follow', [\App\Http\Controllers\TopicController::class, 'follow']);
        Route::delete('/{slug}/follow', [\App\Http\Controllers\TopicController::class, 'unfollow']);
    });

    // Ice Breaker Cards
    Route::get('ice-breakers/questions', [\App\Http\Controllers\IceBreakerController::class, 'getQuestions']);
    Route::post('ice-breakers/answer', [\App\Http\Controllers\IceBreakerController::class, 'submitAnswer']);
    Route::get('ice-breakers/answers/{matchId}', [\App\Http\Controllers\IceBreakerController::class, 'getAnswers']);

    // AI Wingman
    Route::middleware('feature:ai_wingman')->group(function () {
        Route::get('wingman/ice-breakers/{matchId}', [\App\Http\Controllers\AiWingmanController::class, 'getIceBreakers']);
        Route::get('wingman/replies/{matchId}', [\App\Http\Controllers\AiWingmanController::class, 'getReplySuggestions']);
        Route::post('wingman/message-feedback/{matchId}', [\App\Http\Controllers\AiWingmanController::class, 'analyzeDraft']);
        Route::get('wingman/profile-analysis', [\App\Http\Controllers\AiWingmanController::class, 'getProfileAnalysis']);
        Route::get('wingman/date-ideas/general', function (\Illuminate\Http\Request $request) {
            // General date ideas that don't require a specific match
            // Returns static Detroit-curated ideas; AI generation requires a match context
            return response()->json(['ideas' => [
                ['title' => 'Sunset at Belle Isle', 'description' => 'Pack a blanket and watch the skyline from the island.', 'vibe' => 'Romantic', 'estimated_cost' => 'Free'],
                ['title' => 'Jazz at Cliff Bell\'s', 'description' => 'Classic Detroit atmosphere with live world-class jazz.', 'vibe' => 'Sophisticated', 'estimated_cost' => '$$'],
                ['title' => 'Arcade Night at Offworld', 'description' => 'Retro games and pizza in a high-energy setting.', 'vibe' => 'Playful', 'estimated_cost' => '$'],
                ['title' => 'Eastern Market Morning', 'description' => 'Grab coffee and explore the historic market stalls together.', 'vibe' => 'Casual', 'estimated_cost' => '$'],
                ['title' => 'Detroit Institute of Arts', 'description' => 'Wander through world-class galleries and find your favorite piece.', 'vibe' => 'Cultural', 'estimated_cost' => '$'],
                ['title' => 'RiverWalk Stroll', 'description' => 'Walk the Detroit RiverWalk at golden hour for stunning views.', 'vibe' => 'Romantic', 'estimated_cost' => 'Free'],
            ]]);
        });
        Route::get('wingman/date-ideas/{match}', [\App\Http\Controllers\WingmanController::class, 'generateDateIdeas']);
        Route::post('wingman/roast', [\App\Http\Controllers\AiWingmanController::class, 'roastProfile']);
        Route::get('wingman/vibe-check', [\App\Http\Controllers\AiWingmanController::class, 'checkVibe']);
        Route::get('wingman/fortune', [\App\Http\Controllers\AiWingmanController::class, 'predictFortune']);
        Route::get('wingman/cosmic-match', [\App\Http\Controllers\AiWingmanController::class, 'getCosmicMatch']);
        Route::get('wingman/nemesis', [\App\Http\Controllers\AiWingmanController::class, 'findNemesis']);
        Route::post('wingman/quirk-check', [\App\Http\Controllers\AiWingmanController::class, 'checkQuirk']);
        Route::post('wingman/compatibility-audit/{targetId}', [\App\Http\Controllers\AiWingmanController::class, 'compatibilityAudit']);
    });

    // Bulletin Board
    Route::get('bulletin-boards', [BulletinBoardController::class, 'index']);
    Route::post('bulletin-boards', [BulletinBoardController::class, 'createOrFind']);
    Route::get('bulletin-boards/{id}', [BulletinBoardController::class, 'show']);
    Route::post('bulletin-boards/{id}/messages', [BulletinBoardController::class, 'postMessage']);
    Route::get('bulletin-boards/{id}/messages', [BulletinBoardController::class, 'getMessages']);
    Route::post('bulletin-boards/{id}/subscribe', [BulletinBoardController::class, 'subscribe']);
    Route::post('bulletin-boards/{id}/unsubscribe', [BulletinBoardController::class, 'unsubscribe']);

    // Consumer Deals/Promotions Discovery
    Route::get('deals', [\App\Http\Controllers\MerchantController::class, 'browseDeals']);
    Route::get('deals/categories', [\App\Http\Controllers\MerchantController::class, 'getCategories']);
    Route::post('promotions/{id}/track', [\App\Http\Controllers\MerchantController::class, 'trackPromotion']);

    // Matchmaker Bounty
    Route::get('bounties', [\App\Http\Controllers\Api\MatchBountyController::class, 'index']);
    Route::post('bounties', [\App\Http\Controllers\Api\MatchBountyController::class, 'store']);
    Route::get('bounties/{slug}', [\App\Http\Controllers\Api\MatchBountyController::class, 'show']);
    Route::get('bounties/{slug}/suggest', function ($slug) {
        return response()->json(['message' => 'Use POST to suggest']);
    });
    Route::post('bounties/{slug}/suggest', [\App\Http\Controllers\Api\MatchBountyController::class, 'suggest']);

    // Friend routes
    Route::prefix('friends')->group(function () {
        Route::get('/', [FriendController::class, 'getFriends']);
        Route::get('/requests', [FriendController::class, 'getFriendRequests']);
        Route::post('/requests', [FriendController::class, 'sendFriendRequest'])->middleware('throttle:friend_requests');
        Route::post('/requests/{userId}', [FriendController::class, 'respondToFriendRequest']);
        Route::delete('/{friendId}', [FriendController::class, 'removeFriend']);
        Route::get('/search', [FriendController::class, 'search']);
    });

    Route::prefix('relationship-links')->group(function () {
        Route::get('/', [\App\Http\Controllers\RelationshipLinkController::class, 'index']);
        Route::get('/requests', [\App\Http\Controllers\RelationshipLinkController::class, 'pendingRequests']);
        Route::post('/', [\App\Http\Controllers\RelationshipLinkController::class, 'store'])->middleware('throttle:friend_requests');
        Route::post('/{relationshipLink}/respond', [\App\Http\Controllers\RelationshipLinkController::class, 'respond']);
        Route::put('/{relationshipLink}', [\App\Http\Controllers\RelationshipLinkController::class, 'update']);
        Route::delete('/{relationshipLink}', [\App\Http\Controllers\RelationshipLinkController::class, 'destroy']);
    });

    // Location routes (Phase 5A - Location-Based Social Features)
    Route::prefix('location')->group(function () {
        Route::get('aura/{matchId}', [LocationController::class, 'aura']);
        Route::get('/', [LocationController::class, 'show']);
        Route::post('/', [LocationController::class, 'update']);
        Route::put('/privacy', [LocationController::class, 'updatePrivacy']);
        Route::delete('/', [LocationController::class, 'clear']);
        Route::get('/nearby', [LocationController::class, 'nearby']);
    });

    // Verification
    Route::post('verification/verify', [\App\Http\Controllers\VerificationController::class, 'verify'])->middleware('throttle:verification');
    Route::get('verification/status', [\App\Http\Controllers\VerificationController::class, 'status']);

    // Feedback
    Route::post('feedback', [\App\Http\Controllers\FeedbackController::class, 'store'])->middleware('throttle:feedback');
    Route::get('feedback', [\App\Http\Controllers\FeedbackController::class, 'index']);
    Route::put('feedback/{id}', [\App\Http\Controllers\FeedbackController::class, 'update']);

    // Token / Wallet
    Route::get('wallet', [\App\Http\Controllers\Api\TokenController::class, 'balance']);
    Route::post('wallet/withdraw', [\App\Http\Controllers\Api\TokenController::class, 'withdraw']);
    Route::post('wallet/deposit', [\App\Http\Controllers\Api\TokenController::class, 'deposit']);
    Route::post('wallet/top-up/initiate', [\App\Http\Controllers\Api\TokenController::class, 'initiateTopUp']);
    Route::post('wallet/top-up/confirm', [\App\Http\Controllers\Api\TokenController::class, 'confirmTopUp']);
    Route::post('wallet/transfer', [\App\Http\Controllers\Api\TokenController::class, 'transfer']);
    Route::post('wallet/address', [\App\Http\Controllers\Api\TokenController::class, 'updateAddress']);
    Route::get('leaderboard', [\App\Http\Controllers\Api\TokenController::class, 'leaderboard']);

    // Payment Requests
    Route::get('wallet/requests', [\App\Http\Controllers\Api\PaymentRequestController::class, 'index']);
    Route::post('wallet/requests', [\App\Http\Controllers\Api\PaymentRequestController::class, 'store']);
    Route::post('wallet/requests/{id}/pay', [\App\Http\Controllers\Api\PaymentRequestController::class, 'pay']);
    Route::post('wallet/requests/{id}/cancel', [\App\Http\Controllers\Api\PaymentRequestController::class, 'cancel']);

    // Gifts
    Route::get('gifts', [\App\Http\Controllers\GiftController::class, 'index']);
    Route::post('gifts/send', [\App\Http\Controllers\GiftController::class, 'send']);
    Route::get('gifts/received', [\App\Http\Controllers\GiftController::class, 'received']);

    // Wingman
    Route::post('wingman/assist', [\App\Http\Controllers\WingmanController::class, 'recordAssist']);

    // Matchmaker Bounty
    Route::post('matchmaker/bounty', [\App\Http\Controllers\MatchMakerController::class, 'createBounty']);
    Route::post('matchmaker/bounty/{slug}/suggest', [\App\Http\Controllers\MatchMakerController::class, 'suggest']);

    // Share Unlock
    Route::post('share-unlock', [\App\Http\Controllers\ShareUnlockController::class, 'store']);
    Route::get('share-unlock/{targetProfileId}', [\App\Http\Controllers\ShareUnlockController::class, 'check']);

    // Achievements
    Route::get('achievements', [\App\Http\Controllers\AchievementController::class, 'index']);

    // Merchant Pulse API
    Route::middleware(['role:merchant'])->prefix('merchant/pulse')->group(function () {
        Route::get('vibe', [\App\Http\Controllers\MerchantPulseController::class, 'getVibe']);
        Route::post('broadcast', [\App\Http\Controllers\MerchantPulseController::class, 'broadcast']);
        Route::post('{id}/deactivate', [\App\Http\Controllers\MerchantPulseController::class, 'deactivateBroadcast']);
    });

    // Hardware Token API
    Route::prefix('hardware-tokens')->group(function () {
        Route::post('register', [\App\Http\Controllers\HardwareTokenController::class, 'register']);
        Route::post('ping', [\App\Http\Controllers\HardwareTokenController::class, 'ping']);
        Route::get('status', [\App\Http\Controllers\HardwareTokenController::class, 'status']);
    });

    // Moderation (admin/moderator)
    Route::prefix('moderation')->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\ModerationController::class, 'dashboard']);
        Route::get('flagged-content', [\App\Http\Controllers\ModerationController::class, 'flaggedContent']);
        Route::post('flags/{artifactId}/review', [\App\Http\Controllers\ModerationController::class, 'reviewFlag']);
        Route::get('spoof-detections', [\App\Http\Controllers\ModerationController::class, 'spoofDetections']);
        Route::post('spoofs/{detectionId}/review', [\App\Http\Controllers\ModerationController::class, 'reviewSpoof']);
        Route::get('throttles', [\App\Http\Controllers\ModerationController::class, 'activeThrottles']);
        Route::delete('throttles/{throttleId}', [\App\Http\Controllers\ModerationController::class, 'removeThrottle']);
        Route::get('actions', [\App\Http\Controllers\ModerationController::class, 'actionHistory']);
        Route::get('users/{userId}', [\App\Http\Controllers\ModerationController::class, 'userProfile']);
    });

    // Feature Flags / Config (moved to public routes)

    // Wingman Status
    Route::get('wingman/status', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $assistCount = \App\Models\MatchAssist::where('matchmaker_id', $user->id)->count();

        return response()->json([
            'active' => true,
            'assists_count' => $assistCount,
            'earned_tokens' => $assistCount * 10,
            'features' => [
                'ice_breakers' => true,
                'reply_suggestions' => true,
                'profile_analysis' => true,
                'date_ideas' => true,
                'roast' => true,
                'vibe_check' => true,
                'fortune' => true,
                'cosmic_match' => true,
                'nemesis' => true,
            ],
        ]);
    });

    // Referral System (extended)
    Route::get('referrals/link', [\App\Http\Controllers\ReferralController::class, 'summary']);
    Route::get('referrals/payouts', function (\Illuminate\Http\Request $request) {
        $commissions = \App\Models\ReferralCommission::where('beneficiary_user_id', $request->user()->id)
            ->with('purchaser')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($commissions);
    });

    // Travel Mode
    Route::post('location/travel-mode', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_name' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $profile = $user->profile;

        if ($profile) {
            $profile->update([
                'is_travel_mode' => $validated['enabled'],
                'travel_latitude' => $validated['latitude'] ?? null,
                'travel_longitude' => $validated['longitude'] ?? null,
                'travel_location_name' => $validated['location_name'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Travel mode updated',
            'travel_mode' => (bool) ($validated['enabled']),
            'location_name' => $validated['location_name'] ?? null,
        ]);
    });

    // Achievements (extended)
    Route::get('achievements/progress', [\App\Http\Controllers\AchievementController::class, 'index']);
    Route::post('achievements/{achievementId}/unlock', function (\Illuminate\Http\Request $request, $achievementId) {
        $user = $request->user();
        $user->achievements()->syncWithoutDetaching([
            $achievementId => ['unlocked_at' => now()],
        ]);

        return response()->json(['message' => 'Achievement unlocked']);
    });
});
