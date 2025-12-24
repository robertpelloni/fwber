<?php

use Illuminate\Http\Request;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BulletinBoardController;
use App\Http\Controllers\ChatroomController;
use App\Http\Controllers\ChatroomMessageController;
use App\Http\Controllers\ContentGenerationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\MercureAuthController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileViewController;
use App\Http\Controllers\ProximityChatroomController;
use App\Http\Controllers\ProximityChatroomMessageController;
use App\Http\Controllers\RateLimitController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\RelationshipTierController;
use App\Http\Controllers\UserPhysicalProfileController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WebSocketController;
use App\Http\Controllers\ProximityArtifactController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\FriendController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;

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

Route::post('auth/register', [\App\Http\Controllers\AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('auth/login', [\App\Http\Controllers\AuthController::class, 'login'])->middleware('throttle:auth');
Route::get('auth/login', function () {
    return response()->json(['message' => 'Unauthenticated. Please login.'], 401);
})->name('login');
Route::get('auth/referral/{code}', [\App\Http\Controllers\AuthController::class, 'checkReferralCode']);
Route::post('public/vouch', [\App\Http\Controllers\VouchController::class, 'store'])->middleware('throttle:60,1');
Route::post('auth/two-factor-challenge', [\App\Http\Controllers\TwoFactorChallengeController::class, 'store'])->middleware('throttle:auth');

// Public Debug Route (No Auth)
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
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->middleware('auth:sanctum');

// Manual User Fetch Debug Route (No Middleware)
Route::get('debug/user-manual', function () {
    try {
        // 1. Test basic query
        $user = \App\Models\User::first();
        
        if (!$user) {
            return response()->json(['status' => 'no users found']);
        }

        // 2. Test attribute access
        $data = [
            'id' => $user->id,
            'email' => $user->email,
            'has_two_factor' => $user->hasEnabledTwoFactorAuthentication(),
        ];

        // 3. Test Relationship Loading (CRITICAL STEP)
        $user->load(['profile', 'photos']);

        // 4. Test serialization (toArray)
        $array = $user->toArray();

        return response()->json([
            'status' => 'success',
            'manual_data' => $data,
            'serialized_user' => $array,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'error' => 'Manual Fetch Failed',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Empty Auth Route (Test Middleware Only)
Route::get('debug/empty-auth', function () {
    return response()->json(['status' => 'ok', 'message' => 'Middleware passed']);
})->middleware('debug.auth');

// Real Auth Empty Route (Test Actual Middleware)
Route::get('debug/real-auth-empty', function () {
    return response()->json(['status' => 'ok', 'message' => 'Real Auth Middleware passed']);
})->middleware('auth:sanctum');

// Debug Route to test Sanctum Token Logic Manually
Route::get('debug/sanctum-manual', function (Request $request) {
    try {
        // Try getting token from Bearer header OR query parameter
        $token = $request->bearerToken() ?? $request->query('token');
        
        if (!$token) {
            return response()->json(['error' => 'No Bearer Token provided. Pass ?token=... or use Authorization header'], 400);
        }

        // 1. Find the token in DB
        $model = \Laravel\Sanctum\Sanctum::personalAccessTokenModel();
        $accessToken = $model::findToken($token);

        if (!$accessToken) {
            return response()->json(['error' => 'Invalid Token (Not found in DB)'], 401);
        }

        // 2. Check if token is valid
        $user = $accessToken->tokenable; // This triggers the relationship to User

        if (!$user) {
             return response()->json(['error' => 'Token has no user associated'], 401);
        }

        return response()->json([
            'status' => 'success',
            'token_id' => $accessToken->id,
            'user_id' => $accessToken->tokenable_id,
            'user_email' => $user->email,
            'user_class' => get_class($user),
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'error' => 'Sanctum Manual Check Failed',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Debug Route to Generate a Token for the First User
Route::get('debug/get-token', function () {
    try {
        $user = \App\Models\User::first();
        if (!$user) {
            return response()->json(['error' => 'No users found in database'], 404);
        }
        
        // Create a new token
        $token = $user->createToken('debug-token')->plainTextToken;
        
        return response()->json([
            'status' => 'success',
            'user_id' => $user->id,
            'email' => $user->email,
            'token' => $token,
            'usage_url' => url('/api/debug/sanctum-manual?token=' . $token),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'error' => 'Token Generation Failed',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Simulation Route: Manually invoke the Auth Guard
Route::get('debug/simulate-guard', function (Request $request) {
    try {
        // Force the request to use the token from query param if header is missing
        if (!$request->bearerToken() && $request->has('token')) {
            $request->headers->set('Authorization', 'Bearer ' . $request->query('token'));
        }

        // Attempt to authenticate using the Sanctum guard
        // This runs the exact logic that auth:sanctum middleware runs
        $user = \Illuminate\Support\Facades\Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json(['status' => 'failed', 'reason' => 'Auth::guard(sanctum)->user() returned null']);
        }

        return response()->json([
            'status' => 'success',
            'user_id' => $user->id,
            'message' => 'Auth guard successfully resolved user',
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'error' => 'Auth Guard Crashed',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Throttle Test Route
Route::get('debug/throttle-test', function () {
    return response()->json(['status' => 'ok']);
})->middleware('throttle:api');

// Debug Route: List Event Listeners for Authenticated Event
Route::get('debug/listeners', function () {
    $events = [
        'Illuminate\Auth\Events\Authenticated',
        'Illuminate\Auth\Events\Login',
        'Laravel\Sanctum\Events\TokenAuthenticated',
    ];
    
    $results = [];
    
    foreach ($events as $eventName) {
        $listeners = \Illuminate\Support\Facades\Event::getListeners($eventName);
        $listenerNames = [];
        
        foreach ($listeners as $listener) {
            if (is_array($listener)) {
                $class = is_object($listener[0]) ? get_class($listener[0]) : $listener[0];
                $method = $listener[1];
                $listenerNames[] = "$class@$method";
            } elseif (is_object($listener) && $listener instanceof \Closure) {
                $listenerNames[] = 'Closure';
            } elseif (is_string($listener)) {
                $listenerNames[] = $listener;
            } else {
                $listenerNames[] = 'Unknown';
            }
        }
        
        $results[$eventName] = $listenerNames;
    }
    
    return response()->json($results);
});

// Health Checks
Route::get('health', [\App\Http\Controllers\HealthController::class, 'check']);
Route::get('health/liveness', [\App\Http\Controllers\HealthController::class, 'liveness']);
Route::get('health/readiness', [\App\Http\Controllers\HealthController::class, 'readiness']);
Route::get('health/metrics', [\App\Http\Controllers\HealthController::class, 'metrics']);

// Stripe Webhook
Route::post('stripe/webhook', [\App\Http\Controllers\StripeWebhookController::class, 'handleWebhook']);

// Public Viral Content
Route::get('viral-content/{id}', [\App\Http\Controllers\ViralContentController::class, 'show']);

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
    Route::get('/notifications', [DeviceTokenController::class, 'index']);

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
    Route::put('profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword']);
    Route::delete('profile', [\App\Http\Controllers\ProfileController::class, 'destroy']);
    Route::get('profile/completeness', [\App\Http\Controllers\ProfileController::class, 'completeness']);
    Route::get('profile/export', [\App\Http\Controllers\ProfileController::class, 'export']);

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
    Route::get('profile/completeness', [\App\Http\Controllers\DashboardController::class, 'getProfileCompleteness']);

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

    // Video Chat
    Route::middleware('feature:video_chat')->group(function () {
        Route::post('video/signal', [\App\Http\Controllers\VideoChatController::class, 'signal']);
        Route::post('video/initiate', [\App\Http\Controllers\VideoChatController::class, 'initiate']);
        Route::put('video/{id}/status', [\App\Http\Controllers\VideoChatController::class, 'updateStatus']);
        Route::get('video/history', [\App\Http\Controllers\VideoChatController::class, 'history']);
    });

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
    Route::apiResource('groups', \App\Http\Controllers\GroupController::class);

    // Premium
    Route::get('premium/who-likes-you', [\App\Http\Controllers\PremiumController::class, 'getWhoLikesYou']);
    Route::post('premium/initiate', [\App\Http\Controllers\PremiumController::class, 'initiatePurchase']);
    Route::post('premium/purchase', [\App\Http\Controllers\PremiumController::class, 'purchasePremium']);
    Route::get('premium/status', [\App\Http\Controllers\PremiumController::class, 'getPremiumStatus']);

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
    Route::post('proximity/artifacts/{id}/flag', [\App\Http\Controllers\ProximityArtifactController::class, 'flag']);
    Route::delete('proximity/artifacts/{id}', [\App\Http\Controllers\ProximityArtifactController::class, 'destroy']);
    Route::get('proximity/local-pulse', [\App\Http\Controllers\ProximityArtifactController::class, 'localPulse']);

    // Matches
    Route::get('matches', [\App\Http\Controllers\MatchController::class, 'index']);
    Route::get('matches/established', [\App\Http\Controllers\MatchController::class, 'establishedMatches']);
    Route::get('matches/{id}/insights', [\App\Http\Controllers\MatchInsightsController::class, 'show']);
    Route::post('matches/{id}/insights/unlock', [\App\Http\Controllers\MatchInsightsController::class, 'unlock']);
    Route::post('matches/action', [\App\Http\Controllers\MatchController::class, 'action'])->middleware('throttle:matching');

    // Direct Messages
    Route::get('messages/unread-count', [\App\Http\Controllers\MessageController::class, 'unreadCount']);
    Route::post('messages', [\App\Http\Controllers\MessageController::class, 'store'])->middleware('throttle:messaging');
    Route::post('messages/translate', [\App\Http\Controllers\TranslationController::class, 'translate'])->middleware('throttle:content_generation');
    Route::get('messages/{userId}', [\App\Http\Controllers\MessageController::class, 'index']);
    Route::post('messages/{messageId}/read', [\App\Http\Controllers\MessageController::class, 'markAsRead']);
    Route::post('messages/mark-all-read/{senderId}', [\App\Http\Controllers\MessageController::class, 'markAllAsRead']);

    // Chatrooms
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

    // AI Content Generation (rate limited)
    Route::middleware('throttle:content_generation')->group(function () {
        Route::post('content/generate-bio', [\App\Http\Controllers\ContentGenerationController::class, 'generateProfileBio']);
        Route::post('content/generate-posts/{boardId}', [\App\Http\Controllers\ContentGenerationController::class, 'generatePostSuggestions']);
        Route::post('content/generate-starters', [\App\Http\Controllers\ContentGenerationController::class, 'generateConversationStarters']);
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
    Route::get('photos/{id}', [\App\Http\Controllers\PhotoController::class, 'show']);
    Route::put('photos/{id}', [\App\Http\Controllers\PhotoController::class, 'update']);
    Route::delete('photos/{id}', [\App\Http\Controllers\PhotoController::class, 'destroy']);

    // Media Analysis
    Route::middleware('feature:media_analysis')->group(function () {
        Route::post('media/analyze', [\App\Http\Controllers\MediaAnalysisController::class, 'analyze']);
    });

    // AI Wingman
    Route::middleware('feature:ai_wingman')->group(function () {
        Route::get('wingman/ice-breakers/{matchId}', [\App\Http\Controllers\AiWingmanController::class, 'getIceBreakers']);
        Route::get('wingman/replies/{matchId}', [\App\Http\Controllers\AiWingmanController::class, 'getReplySuggestions']);
        Route::post('wingman/message-feedback/{matchId}', [\App\Http\Controllers\AiWingmanController::class, 'analyzeDraft']);
        Route::get('wingman/profile-analysis', [\App\Http\Controllers\AiWingmanController::class, 'getProfileAnalysis']);
        Route::get('wingman/date-ideas/{matchId}', [\App\Http\Controllers\AiWingmanController::class, 'getDateIdeas']);
        Route::post('wingman/roast', [\App\Http\Controllers\AiWingmanController::class, 'roastProfile']);
        Route::get('wingman/vibe-check', [\App\Http\Controllers\AiWingmanController::class, 'checkVibe']);
        Route::get('wingman/fortune', [\App\Http\Controllers\AiWingmanController::class, 'predictFortune']);
        Route::get('wingman/cosmic-match', [\App\Http\Controllers\AiWingmanController::class, 'getCosmicMatch']);
        Route::get('wingman/nemesis', [\App\Http\Controllers\AiWingmanController::class, 'findNemesis']);
    });

    // Friend routes
    Route::prefix('friends')->group(function () {
        Route::get('/', [FriendController::class, 'getFriends']);
        Route::get('/requests', [FriendController::class, 'getFriendRequests']);
        Route::post('/requests', [FriendController::class, 'sendFriendRequest'])->middleware('throttle:friend_requests');
        Route::post('/requests/{userId}', [FriendController::class, 'respondToFriendRequest']);
        Route::delete('/{friendId}', [FriendController::class, 'removeFriend']);
        Route::get('/search', [FriendController::class, 'search']);
    });

    // Location routes (Phase 5A - Location-Based Social Features)
    Route::get("/location", [LocationController::class, "show"]);
    Route::post("/location", [LocationController::class, "update"]);
    Route::put("/location/privacy", [LocationController::class, "updatePrivacy"]);
    Route::delete("/location", [LocationController::class, "clear"]);
    Route::get("/location/nearby", [LocationController::class, "nearby"]);

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
    Route::post('wallet/transfer', [\App\Http\Controllers\Api\TokenController::class, 'transfer']);
    Route::post('wallet/address', [\App\Http\Controllers\Api\TokenController::class, 'updateAddress']);
    Route::get('leaderboard', [\App\Http\Controllers\Api\TokenController::class, 'leaderboard']);

    // Gifts
    Route::get('gifts', [\App\Http\Controllers\GiftController::class, 'index']);
    Route::post('gifts/send', [\App\Http\Controllers\GiftController::class, 'send']);
    Route::get('gifts/received', [\App\Http\Controllers\GiftController::class, 'received']);

    // Wingman
    Route::post('wingman/assist', [\App\Http\Controllers\WingmanController::class, 'recordAssist']);

    // Share Unlock
    Route::post('share-unlock', [\App\Http\Controllers\ShareUnlockController::class, 'store']);
    Route::get('share-unlock/{targetProfileId}', [\App\Http\Controllers\ShareUnlockController::class, 'check']);

    // Achievements
    Route::get('achievements', [\App\Http\Controllers\AchievementController::class, 'index']);
});

