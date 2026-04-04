<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - CORE ONLY (Simplified)
|--------------------------------------------------------------------------
| The site is focused strictly on proximity-based hookups based on mutual preference.
*/

// Auth
Route::post('auth/register', [\App\Http\Controllers\AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('auth/login', [\App\Http\Controllers\AuthController::class, 'login'])->middleware('throttle:auth');
Route::get('auth/login', function () {
    return response()->json(['message' => 'Unauthenticated. Please login.'], 401);
})->name('login');
Route::post('auth/two-factor-challenge', [\App\Http\Controllers\TwoFactorChallengeController::class, 'store'])->middleware('throttle:auth');

// Public Debug Route (No Auth)
if (! app()->isProduction()) {
    Route::get('debug/public', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    });
}

// Lightweight analytics ingestion is intentionally public/optional-auth so page-view
// tracking from the web shell can succeed before login and without generating 404 noise.
Route::get('health', [\App\Http\Controllers\HealthController::class, 'check']);
Route::get('health/liveness', [\App\Http\Controllers\HealthController::class, 'liveness']);
Route::get('health/readiness', [\App\Http\Controllers\HealthController::class, 'readiness']);
Route::get('health/metrics', [\App\Http\Controllers\HealthController::class, 'metrics']);
Route::post('analytics/events', [\App\Http\Controllers\AnalyticsController::class, 'store']);
Route::post('public/roast', [\App\Http\Controllers\AiWingmanController::class, 'roastPublic']);
Route::post('stripe/webhook', [\App\Http\Controllers\StripeWebhookController::class, 'handleWebhook']);
Route::get('marketplace/nearby', [\App\Http\Controllers\MerchantInventoryController::class, 'nearby']);
Route::get('marketplace/{merchantId}', [\App\Http\Controllers\MerchantInventoryController::class, 'showMarketplace']);

Route::middleware('auth:sanctum')->group(function () {
    
    // Auth (Logged In)
    Route::post('auth/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
    Route::get('auth/me', [\App\Http\Controllers\AuthController::class, 'me']);
    
    // Two Factor Authentication
    Route::post('user/two-factor-authentication', [\App\Http\Controllers\TwoFactorAuthenticationController::class, 'store']);
    Route::post('user/confirmed-two-factor-authentication', [\App\Http\Controllers\TwoFactorAuthenticationController::class, 'confirm']);
    Route::delete('user/two-factor-authentication', [\App\Http\Controllers\TwoFactorAuthenticationController::class, 'destroy']);
    Route::get('user/two-factor-qr-code', [\App\Http\Controllers\TwoFactorAuthenticationController::class, 'showQrCode']);

    // Profile & Photos
    Route::get('profile', [\App\Http\Controllers\ProfileController::class, 'show']);
    Route::get('profile/completeness', [\App\Http\Controllers\DashboardController::class, 'getProfileCompleteness']);
    Route::put('profile', [\App\Http\Controllers\ProfileController::class, 'update']);
    Route::delete('profile', [\App\Http\Controllers\ProfileController::class, 'destroy']);
    Route::get('users/{id}', [\App\Http\Controllers\ProfileController::class, 'showPublic']);

    Route::get('onboarding/status', [\App\Http\Controllers\OnboardingController::class, 'getStatus']);
    Route::post('onboarding/complete', [\App\Http\Controllers\OnboardingController::class, 'complete']);

    Route::get('physical-profile', [\App\Http\Controllers\UserPhysicalProfileController::class, 'show']);
    Route::put('physical-profile', [\App\Http\Controllers\UserPhysicalProfileController::class, 'upsert']);

    Route::middleware('throttle:photo_uploads')->group(function () {
        Route::post('photos', [\App\Http\Controllers\PhotoController::class, 'store']);
        Route::post('photos/reorder', [\App\Http\Controllers\PhotoController::class, 'reorder']);
    });
    Route::get('photos', [\App\Http\Controllers\PhotoController::class, 'index']);
    Route::get('photos/{id}', [\App\Http\Controllers\PhotoController::class, 'show']);
    Route::delete('photos/{id}', [\App\Http\Controllers\PhotoController::class, 'destroy']);

    // Security & E2E Keys
    Route::prefix('security/keys')->group(function () {
        Route::post('/', [\App\Http\Controllers\E2EKeyManagementController::class, 'store']);
        Route::get('/me', [\App\Http\Controllers\E2EKeyManagementController::class, 'me']);
        Route::post('/backup', [\App\Http\Controllers\E2EKeyManagementController::class, 'backup']);
        Route::get('/restore', [\App\Http\Controllers\E2EKeyManagementController::class, 'restore']);
        Route::get('/{userId}', [\App\Http\Controllers\E2EKeyManagementController::class, 'show']);
    });

    // Location & Proximity Match Discovery
    Route::prefix('location')->group(function () {
        Route::get('/', [\App\Http\Controllers\LocationController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\LocationController::class, 'update']);
        Route::put('/privacy', [\App\Http\Controllers\LocationController::class, 'updatePrivacy']);
        Route::get('/nearby', [\App\Http\Controllers\LocationController::class, 'nearby']);
    });

    // Match Engine
    Route::get('matches', [\App\Http\Controllers\MatchController::class, 'index']);
    Route::get('matches/established', [\App\Http\Controllers\MatchController::class, 'establishedMatches']);
    Route::post('matches/action', [\App\Http\Controllers\MatchController::class, 'action'])->middleware('throttle:matching');
    Route::post('matches/nfc-exchange', [\App\Http\Controllers\MatchController::class, 'nfcExchange']);

    // Messaging (E2E)
    Route::get('messages/unread-count', [\App\Http\Controllers\MessageController::class, 'unreadCount']);
    Route::post('messages/sync-batch', [\App\Http\Controllers\MessageController::class, 'syncBatch'])->middleware('throttle:messaging');
    Route::post('messages', [\App\Http\Controllers\MessageController::class, 'store'])->middleware('throttle:messaging');
    Route::get('messages/{userId}', [\App\Http\Controllers\MessageController::class, 'index']);
    Route::post('messages/{messageId}/read', [\App\Http\Controllers\MessageController::class, 'markAsRead']);

    // Merchant / Marketplace
    Route::post('merchant-portal/register', [\App\Http\Controllers\MerchantController::class, 'register']);
    Route::get('merchant-portal/profile', [\App\Http\Controllers\MerchantController::class, 'profile']);
    Route::put('merchant-portal/profile', [\App\Http\Controllers\MerchantController::class, 'updateProfile']);
    Route::get('merchant-portal/dashboard', [\App\Http\Controllers\MerchantController::class, 'dashboard']);
    Route::get('merchant-portal/analytics', [\App\Http\Controllers\MerchantAnalyticsController::class, 'index']);
    Route::get('merchant-portal/inventory', [\App\Http\Controllers\MerchantInventoryController::class, 'index']);
    Route::post('merchant-portal/inventory', [\App\Http\Controllers\MerchantInventoryController::class, 'store']);
    Route::put('merchant-portal/inventory/{id}', [\App\Http\Controllers\MerchantInventoryController::class, 'update']);
    Route::delete('merchant-portal/inventory/{id}', [\App\Http\Controllers\MerchantInventoryController::class, 'destroy']);
    Route::post('merchant-portal/inventory/redeem', [\App\Http\Controllers\MerchantInventoryController::class, 'redeem']);
    Route::post('marketplace/purchase/{itemId}', [\App\Http\Controllers\MerchantInventoryController::class, 'purchase']);

    // Premium / Billing
    Route::get('premium/plans', [\App\Http\Controllers\PremiumController::class, 'plans']);
    Route::get('premium/status', [\App\Http\Controllers\PremiumController::class, 'getPremiumStatus']);
    Route::get('premium/history', [\App\Http\Controllers\PremiumController::class, 'history']);
    Route::post('premium/initiate', [\App\Http\Controllers\PremiumController::class, 'initiatePurchase']);
    Route::post('premium/purchase', [\App\Http\Controllers\PremiumController::class, 'purchasePremium']);
    Route::get('premium/who-likes-you', [\App\Http\Controllers\PremiumController::class, 'getWhoLikesYou']);

    // AI Wingman
    Route::get('wingman/ice-breakers/{matchId}', [\App\Http\Controllers\AiWingmanController::class, 'getIceBreakers']);
    Route::get('wingman/replies/{matchId}', [\App\Http\Controllers\AiWingmanController::class, 'getReplySuggestions']);
    Route::post('wingman/draft-analysis/{matchId}', [\App\Http\Controllers\AiWingmanController::class, 'analyzeDraft']);
    Route::get('wingman/profile-analysis', [\App\Http\Controllers\AiWingmanController::class, 'getProfileAnalysis']);
    Route::get('wingman/date-ideas/{matchId}', [\App\Http\Controllers\AiWingmanController::class, 'getDateIdeas']);
    Route::post('wingman/roast', [\App\Http\Controllers\AiWingmanController::class, 'roastProfile']);
    Route::match(['get', 'post'], 'wingman/vibe-check', [\App\Http\Controllers\AiWingmanController::class, 'checkVibe']);
    Route::match(['get', 'post'], 'wingman/fortune', [\App\Http\Controllers\AiWingmanController::class, 'predictFortune']);
    Route::match(['get', 'post'], 'wingman/cosmic-match', [\App\Http\Controllers\AiWingmanController::class, 'getCosmicMatch']);
    Route::get('wingman/nemesis', [\App\Http\Controllers\AiWingmanController::class, 'findNemesis']);
    Route::post('wingman/quirk-check', [\App\Http\Controllers\AiWingmanController::class, 'checkQuirk']);
    Route::post('wingman/compatibility-audit/{targetId}', [\App\Http\Controllers\AiWingmanController::class, 'compatibilityAudit']);

    // WebSockets (Reverb)
    Route::get('websocket/token', [\App\Http\Controllers\WebSocketController::class, 'getToken']);
    Route::post('websocket/connect', [\App\Http\Controllers\WebSocketController::class, 'connect']);
    Route::post('websocket/message', [\App\Http\Controllers\WebSocketController::class, 'sendMessage']);
    Route::post('websocket/typing', [\App\Http\Controllers\WebSocketController::class, 'sendTypingIndicator']);
    Route::post('websocket/presence', [\App\Http\Controllers\WebSocketController::class, 'updatePresence']);
    Route::get('websocket/online-users', [\App\Http\Controllers\WebSocketController::class, 'getOnlineUsers']);

    // Notifications
    Route::get('notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::get('notifications/count', [\App\Http\Controllers\NotificationController::class, 'count']);
    Route::post('notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
    Route::get('notification-preferences', [\App\Http\Controllers\NotificationPreferenceController::class, 'index']);
    Route::put('notification-preferences/{type}', [\App\Http\Controllers\NotificationPreferenceController::class, 'update']);
    Route::prefix('device-tokens')->group(function (): void {
        Route::post('/', [\App\Http\Controllers\DeviceTokenController::class, 'store']);
        Route::delete('/{token}', [\App\Http\Controllers\DeviceTokenController::class, 'destroy']);
    });

    // Safety & Moderation
    Route::prefix('blocks')->group(function (): void {
        Route::post('/', [\App\Http\Controllers\BlockController::class, 'store']);
        Route::get('/', [\App\Http\Controllers\BlockController::class, 'index']);
        Route::delete('/{userId}', [\App\Http\Controllers\BlockController::class, 'destroy']);
    });
    Route::post('reports', [\App\Http\Controllers\ReportController::class, 'store']);
    Route::get('moderation/dashboard', [\App\Http\Controllers\ModerationController::class, 'dashboard']);
    Route::get('moderation/flagged-content', [\App\Http\Controllers\ModerationController::class, 'flaggedContent']);
    Route::post('moderation/flags/{artifactId}/review', [\App\Http\Controllers\ModerationController::class, 'reviewFlag']);
    Route::get('moderation/spoof-detections', [\App\Http\Controllers\ModerationController::class, 'spoofDetections']);
    Route::post('moderation/spoofs/{detectionId}/review', [\App\Http\Controllers\ModerationController::class, 'reviewSpoof']);
    Route::get('moderation/throttles', [\App\Http\Controllers\ModerationController::class, 'activeThrottles']);
    Route::delete('moderation/throttles/{throttleId}', [\App\Http\Controllers\ModerationController::class, 'removeThrottle']);
    Route::get('moderation/actions', [\App\Http\Controllers\ModerationController::class, 'actionHistory']);
    Route::get('moderation/users/{userId}', [\App\Http\Controllers\ModerationController::class, 'userProfile']);
    Route::get('moderation/merchants', [\App\Http\Controllers\MerchantModerationController::class, 'index']);
    Route::patch('moderation/merchants/{merchantId}', [\App\Http\Controllers\MerchantModerationController::class, 'review']);
    Route::post('safety/panic', [\App\Http\Controllers\SafetyController::class, 'triggerPanic']);
    Route::get('safety/walk/active', [\App\Http\Controllers\SafetyController::class, 'getActiveWalk']);
    Route::post('safety/walk/start', [\App\Http\Controllers\SafetyController::class, 'startSafeWalk']);
    Route::post('safety/walk/{walkId}/end', [\App\Http\Controllers\SafetyController::class, 'endSafeWalk']);

    // ZK-Identity & Hardware Tokens
    Route::prefix('identity')->group(function () {
        Route::post('verify-zk', [\App\Http\Controllers\IdentityController::class, 'verify']);
        Route::get('status', [\App\Http\Controllers\IdentityController::class, 'status']);
    });

    Route::prefix('verification')->group(function () {
        Route::post('verify', [\App\Http\Controllers\VerificationController::class, 'verify']);
        Route::get('status', [\App\Http\Controllers\VerificationController::class, 'status']);
    });

    Route::prefix('hardware-tokens')->group(function () {
        Route::post('register', [\App\Http\Controllers\HardwareTokenController::class, 'register']);
        Route::post('ping', [\App\Http\Controllers\HardwareTokenController::class, 'ping']);
        Route::get('status', [\App\Http\Controllers\HardwareTokenController::class, 'status']);
    });
});
