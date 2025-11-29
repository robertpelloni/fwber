<?php

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
use App\Http\Controllers\TelemetryController;
use App\Http\Controllers\TelemetryReportController;
use App\Http\Controllers\RateLimitController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Api\RelationshipTierController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\UserPhysicalProfileController;
use App\Http\Controllers\Api\BlockController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\GroupController as NewGroupController;
use App\Http\Controllers\GroupPostController;
use App\Http\Controllers\Api\GroupMessageController;
use App\Http\Controllers\WebSocketController;
use App\Http\Controllers\ProximityArtifactController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\FriendController;
use App\Http\Controllers\PhotoRevealController;
use App\Http\Controllers\ConfigController;
use Illuminate\Support\Facades\Route;

Route::middleware("api")->group(function (): void {
    // Health check endpoints (no auth required)
    Route::get('/health', [HealthController::class, 'check']);
    Route::get('/health/liveness', [HealthController::class, 'liveness']);
    Route::get('/health/readiness', [HealthController::class, 'readiness']);
    
    Route::middleware(['throttle:auth'])->group(function () {
        Route::post("/auth/register", [AuthController::class, "register"]);
        Route::post("/auth/login", [AuthController::class, "login"]);
    });

    Route::middleware(["auth.api"])->group(function (): void {
        // Profile routes (Phase 3A - Multi-AI Implementation)
        Route::get("/user", [ProfileController::class, "show"]);
        Route::put("/user", [ProfileController::class, "update"]);
        // Aliases to align with OpenAPI docs while preserving existing routes
        Route::get("/profile", [ProfileController::class, "show"]);
        Route::put("/profile", [ProfileController::class, "update"]);
        Route::get("/profile/completeness", [ProfileController::class, "completeness"]);
        
        // Dashboard routes
        Route::get("/dashboard/stats", [DashboardController::class, "getStats"]);
        Route::get("/dashboard/activity", [DashboardController::class, "getActivity"]);
        
        // Profile view tracking routes
        Route::post("/profile/{userId}/view", [ProfileViewController::class, "recordView"]);
        Route::get("/profile/{userId}/views", [ProfileViewController::class, "getViews"]);
        Route::get("/profile/{userId}/views/stats", [ProfileViewController::class, "getStats"]);
        
        Route::post("/auth/logout", [AuthController::class, "logout"]);

        Route::post('/telemetry/client-events', [TelemetryController::class, 'storeClientEvents']);

        Route::middleware(['feature:analytics', 'auth.moderator'])->group(function (): void {
            Route::get('/telemetry/preview-summary', [TelemetryReportController::class, 'previewSummary']);
        });
        
        // Matching routes (require complete profile)
        Route::middleware('profile.complete')->group(function (): void {
            Route::get("/matches/established", [MatchController::class, "establishedMatches"]);
            Route::get("/matches", [MatchController::class, "index"]);
            Route::post("/matches/action", [MatchController::class, "action"]);
        });

        // Premium routes
        Route::prefix("premium")->group(function (): void {
            Route::get("/status", [\App\Http\Controllers\PremiumController::class, "getPremiumStatus"]);
            Route::post("/purchase", [\App\Http\Controllers\PremiumController::class, "purchasePremium"]);
            Route::middleware(\App\Http\Middleware\RequiresPremium::class)->group(function () {
                Route::get("/who-likes-you", [\App\Http\Controllers\PremiumController::class, "getWhoLikesYou"]);
            });
        });

        // Boost routes
        Route::prefix("boosts")->group(function (): void {
            Route::post("/purchase", [\App\Http\Controllers\BoostController::class, "purchaseBoost"]);
            Route::get("/active", [\App\Http\Controllers\BoostController::class, "getActiveBoost"]);
            Route::get("/history", [\App\Http\Controllers\BoostController::class, "getBoostHistory"]);
        });
        
        // Message routes with tier tracking
        Route::prefix("messages")->group(function (): void {
            Route::get("/unread-count", [MessageController::class, "unreadCount"]);
            Route::get("/{userId}", [MessageController::class, "index"]);
            Route::post("/", [MessageController::class, "store"]);
            Route::post("/{messageId}/read", [MessageController::class, "markAsRead"]);
            Route::post("/mark-all-read/{senderId}", [MessageController::class, "markAllAsRead"]);
        });
        
            // Group routes
            Route::prefix("groups")->group(function (): void {
                Route::get("/", [NewGroupController::class, "index"]);
                Route::post("/", [NewGroupController::class, "store"]);
                Route::get("/my-groups", [NewGroupController::class, "myGroups"]);
                Route::get("/discover", [GroupController::class, "discover"]);
                Route::get("/{groupId}", [NewGroupController::class, "show"]);
                Route::put("/{groupId}", [GroupController::class, "update"]);
                Route::delete("/{groupId}", [GroupController::class, "destroy"]);
                Route::post("/{groupId}/join", [NewGroupController::class, "join"]);
                Route::post("/{groupId}/leave", [NewGroupController::class, "leave"]);
                
                // Group Posts
                Route::get("/{groupId}/posts", [GroupPostController::class, "index"]);
                Route::post("/{groupId}/posts", [GroupPostController::class, "store"]);
                Route::delete("/posts/{postId}", [GroupPostController::class, "destroy"]);

                Route::post("/{groupId}/ownership/transfer", [GroupController::class, "transferOwnership"]);
                Route::post("/{groupId}/members/{memberUserId}/role", [GroupController::class, "setRole"]);
                Route::post("/{groupId}/members/{memberUserId}/ban", [GroupController::class, "banMember"]);
                Route::post("/{groupId}/members/{memberUserId}/unban", [GroupController::class, "unbanMember"]);
                Route::post("/{groupId}/members/{memberUserId}/kick", [GroupController::class, "kickMember"]);
                Route::post("/{groupId}/members/{memberUserId}/mute", [GroupController::class, "muteMember"]);
                Route::post("/{groupId}/members/{memberUserId}/unmute", [GroupController::class, "unmuteMember"]);
                Route::get("/{groupId}/stats", [GroupController::class, "stats"]);
            });
        
            // Group message routes
            Route::prefix("groups/{groupId}/messages")->group(function (): void {
                Route::get("/", [GroupMessageController::class, "index"]);
                Route::post("/", [GroupMessageController::class, "store"]);
            });
        
            Route::post("/group-messages/{messageId}/read", [GroupMessageController::class, "markAsRead"]);
            Route::get("/group-messages/unread-count", [GroupMessageController::class, "unreadCount"]);
        
        // Relationship Tier routes
        Route::prefix("matches/{matchId}")->group(function (): void {
            Route::get("/tier", [RelationshipTierController::class, "show"]);
            Route::post("/tier/update", [RelationshipTierController::class, "update"]);
            Route::get("/photos", [RelationshipTierController::class, "getPhotos"]);
        });

        // Physical Profile & Avatar routes
        Route::prefix('physical-profile')->group(function (): void {
            Route::get('/', [UserPhysicalProfileController::class, 'show']);
            Route::put('/', [UserPhysicalProfileController::class, 'upsert']);
            Route::post('/avatar/request', [UserPhysicalProfileController::class, 'requestAvatar']);
        });

        // Device Token routes for Push Notifications
        Route::prefix('device-tokens')->group(function (): void {
            Route::post('/', [DeviceTokenController::class, 'store']);
            Route::delete('/{token}', [DeviceTokenController::class, 'destroy']);
        });

        // Notification routes
        Route::get('/notifications', [DeviceTokenController::class, 'index']);

        // Friend routes
        Route::prefix('friends')->group(function () {
            Route::get('/', [FriendController::class, 'getFriends']);
            Route::get('/requests', [FriendController::class, 'getFriendRequests']);
            Route::post('/requests', [FriendController::class, 'sendFriendRequest']);
            Route::post('/requests/{userId}', [FriendController::class, 'respondToFriendRequest']);
            Route::delete('/{friendId}', [FriendController::class, 'removeFriend']);
            Route::get('/search', [FriendController::class, 'search']);
        });

        // Block routes
        Route::prefix('blocks')->group(function (): void {
            Route::post('/', [BlockController::class, 'store']);
            Route::delete('/{blockedId}', [BlockController::class, 'destroy']);
        });

        // Report routes
        Route::prefix('reports')->group(function (): void {
            Route::post('/', [ReportController::class, 'store']);
            Route::get('/', [ReportController::class, 'index']);
            Route::put('/{reportId}', [ReportController::class, 'update']);
        });
        
        // Photo routes (Phase 4A - Multi-AI Photo Upload System)
        Route::get("/photos", [PhotoController::class, "index"]);
        Route::middleware('rate.limit:photo_upload')->post("/photos", [PhotoController::class, "store"]);
        Route::put("/photos/{id}", [PhotoController::class, "update"]);
        Route::delete("/photos/{id}", [PhotoController::class, "destroy"]);
        Route::post("/photos/reorder", [PhotoController::class, "reorder"]);
        
        // Photo Reveal routes
        Route::post("/photos/{photo}/reveal", [PhotoRevealController::class, "reveal"]);
        Route::get("/photos/{photo}/original", [PhotoRevealController::class, "original"]);

        // Location routes (Phase 5A - Location-Based Social Features)
        Route::get("/location", [LocationController::class, "show"]);
        Route::middleware('rate.limit:location_update')->post("/location", [LocationController::class, "update"]);
        Route::put("/location/privacy", [LocationController::class, "updatePrivacy"]);
        Route::delete("/location", [LocationController::class, "clear"]);
        Route::get("/location/nearby", [LocationController::class, "nearby"]);

        // Proximity Artifacts (Unified Ephemeral Layer)
        Route::prefix('proximity')->middleware('feature:proximity_artifacts')->group(function (): void {
            Route::get('/feed', [ProximityArtifactController::class, 'index']);
            Route::get('/local-pulse', [ProximityArtifactController::class, 'localPulse']); // Merged feed: artifacts + match candidates
            Route::post('/artifacts', [ProximityArtifactController::class, 'store']);
            Route::get('/artifacts/{id}', [ProximityArtifactController::class, 'show']);
            Route::post('/artifacts/{id}/flag', [ProximityArtifactController::class, 'flag']);
            Route::delete('/artifacts/{id}', [ProximityArtifactController::class, 'destroy']);
        });

        // (moved) Moderation routes defined outside the rate-limited group below
        
        // Bulletin Board routes (Phase 5B - Location-Based Bulletin Board System)
        Route::get("/bulletin-boards", [BulletinBoardController::class, "index"]);
        Route::get("/bulletin-boards/{id}", [BulletinBoardController::class, "show"]);
        Route::post("/bulletin-boards", [BulletinBoardController::class, "createOrFind"]);
        Route::middleware('rate.limit:bulletin_post')->group(function () {
            Route::post("/bulletin-boards/{id}/messages", [BulletinBoardController::class, "postMessage"]);
        });
        Route::get("/bulletin-boards/{id}/messages", [BulletinBoardController::class, "getMessages"]);
        
        // Chatroom routes (Phase 6A - Real-time Location-Based Chatrooms)
        Route::prefix("chatrooms")->middleware('feature:chatrooms')->group(function (): void {
            Route::get("/", [ChatroomController::class, "index"]);
            Route::get("/categories", [ChatroomController::class, "categories"]);
            Route::get("/popular", [ChatroomController::class, "popular"]);
            Route::get("/search", [ChatroomController::class, "search"]);
            Route::get("/my", [ChatroomController::class, "myChatrooms"]);
            Route::post("/", [ChatroomController::class, "store"]);
            Route::get("/{id}", [ChatroomController::class, "show"]);
            Route::put("/{id}", [ChatroomController::class, "update"]);
            Route::delete("/{id}", [ChatroomController::class, "destroy"]);
            Route::post("/{id}/join", [ChatroomController::class, "join"]);
            Route::post("/{id}/leave", [ChatroomController::class, "leave"]);
            Route::get("/{id}/members", [ChatroomController::class, "members"]);
        });
        
        // Chatroom Message routes (Phase 6A - Real-time Location-Based Chatrooms)
        Route::prefix("chatrooms/{chatroomId}/messages")->middleware('feature:chatrooms')->group(function (): void {
            Route::get("/", [ChatroomMessageController::class, "index"]);
            Route::post("/", [ChatroomMessageController::class, "store"]);
            Route::get("/pinned", [ChatroomMessageController::class, "pinned"]);
            Route::get("/{messageId}", [ChatroomMessageController::class, "show"]);
            Route::put("/{messageId}", [ChatroomMessageController::class, "update"]);
            Route::delete("/{messageId}", [ChatroomMessageController::class, "destroy"]);
            Route::post("/{messageId}/reactions", [ChatroomMessageController::class, "addReaction"]);
            Route::delete("/{messageId}/reactions", [ChatroomMessageController::class, "removeReaction"]);
            Route::post("/{messageId}/pin", [ChatroomMessageController::class, "pin"]);
            Route::delete("/{messageId}/pin", [ChatroomMessageController::class, "unpin"]);
            Route::get("/{messageId}/replies", [ChatroomMessageController::class, "replies"]);
        });
        
        // Proximity Chatroom routes (Phase 6B - Proximity-Based Networking Chatrooms)
        Route::prefix("proximity-chatrooms")->middleware('feature:proximity_chatrooms')->group(function (): void {
            Route::get("/nearby", [ProximityChatroomController::class, "findNearby"]);
            Route::post("/", [ProximityChatroomController::class, "create"]);
            Route::get("/{id}", [ProximityChatroomController::class, "show"]);
            Route::post("/{id}/join", [ProximityChatroomController::class, "join"]);
            Route::post("/{id}/leave", [ProximityChatroomController::class, "leave"]);
            Route::post("/{id}/location", [ProximityChatroomController::class, "updateLocation"]);
            Route::get("/{id}/members", [ProximityChatroomController::class, "members"]);
            Route::get("/{id}/networking", [ProximityChatroomController::class, "nearbyNetworking"]);
            Route::get("/{id}/analytics", [ProximityChatroomController::class, "analytics"]);
        });
        
        // Proximity Chatroom Message routes (Phase 6B - Proximity-Based Networking Chatrooms)
        Route::prefix("proximity-chatrooms/{chatroomId}/messages")->middleware('feature:proximity_chatrooms')->group(function (): void {
            Route::get("/", [ProximityChatroomMessageController::class, "index"]);
            Route::post("/", [ProximityChatroomMessageController::class, "store"]);
            Route::get("/pinned", [ProximityChatroomMessageController::class, "pinned"]);
            Route::get("/networking", [ProximityChatroomMessageController::class, "networking"]);
            Route::get("/social", [ProximityChatroomMessageController::class, "social"]);
            Route::get("/{messageId}", [ProximityChatroomMessageController::class, "show"]);
            Route::put("/{messageId}", [ProximityChatroomMessageController::class, "update"]);
            Route::delete("/{messageId}", [ProximityChatroomMessageController::class, "destroy"]);
            Route::post("/{messageId}/reactions", [ProximityChatroomMessageController::class, "addReaction"]);
            Route::delete("/{messageId}/reactions", [ProximityChatroomMessageController::class, "removeReaction"]);
            Route::post("/{messageId}/pin", [ProximityChatroomMessageController::class, "pin"]);
            Route::delete("/{messageId}/pin", [ProximityChatroomMessageController::class, "unpin"]);
            Route::get("/{messageId}/replies", [ProximityChatroomMessageController::class, "replies"]);
        });
        
        // Mercure SSE Broker routes
        Route::get("/mercure/cookie", [MercureAuthController::class, "cookie"]);
        Route::get("/mercure/status", [MercureAuthController::class, "status"]);
        
        // Analytics routes (admin only)
        Route::middleware('feature:analytics')->group(function (): void {
            Route::get("/analytics", [AnalyticsController::class, "index"]);
            Route::get("/analytics/realtime", [AnalyticsController::class, "realtime"]);
            Route::get("/analytics/moderation", [AnalyticsController::class, "moderation"]);
        });
        
        // Recommendation routes (AI-powered personalization)
        Route::prefix("recommendations")->middleware('feature:recommendations')->group(function (): void {
            Route::get("/", [RecommendationController::class, "index"]);
            Route::get("/trending", [RecommendationController::class, "trending"]);
            Route::get("/feed", [RecommendationController::class, "feed"]);
            Route::get("/type/{type}", [RecommendationController::class, "byType"]);
            Route::post("/feedback", [RecommendationController::class, "feedback"]);
            Route::get("/analytics", [RecommendationController::class, "analytics"]);
        });

            // WebSocket routes (bidirectional real-time communication)
            Route::prefix("websocket")->middleware('feature:websocket')->group(function (): void {
                Route::post("/connect", [WebSocketController::class, "connect"]);
                Route::post("/disconnect", [WebSocketController::class, "disconnect"]);
                Route::post("/message", [WebSocketController::class, "sendMessage"]);
                Route::post("/typing", [WebSocketController::class, "sendTypingIndicator"]);
                Route::post("/presence", [WebSocketController::class, "updatePresence"]);
                Route::post("/notification", [WebSocketController::class, "sendNotification"]);
                Route::get("/online-users", [WebSocketController::class, "getOnlineUsers"]);
                Route::get("/connections", [WebSocketController::class, "getUserConnections"]);
                Route::get("/status", [WebSocketController::class, "status"]);
                Route::post("/broadcast", [WebSocketController::class, "broadcast"]);
            });

            // Content Generation routes (AI-powered content creation and optimization)
            // Apply advanced rate limiting (content_generation bucket)
            Route::prefix("content-generation")->middleware(['feature:content_generation', 'rate.limit:content_generation'])->group(function (): void {
                Route::post("/profile", [ContentGenerationController::class, "generateProfileContent"]);
                Route::post("/posts/{boardId}/suggestions", [ContentGenerationController::class, "generatePostSuggestions"]);
                Route::post("/conversation-starters", [ContentGenerationController::class, "generateConversationStarters"]);
                Route::post("/optimize", [ContentGenerationController::class, "optimizeContent"]);
                Route::get("/stats", [ContentGenerationController::class, "getGenerationStats"]);
                Route::get("/optimization-stats", [ContentGenerationController::class, "getOptimizationStats"]);
                Route::post("/feedback", [ContentGenerationController::class, "submitContentFeedback"]);
                Route::get("/history", [ContentGenerationController::class, "getGenerationHistory"]);
                Route::delete("/content/{contentId}", [ContentGenerationController::class, "deleteGeneratedContent"]);
            });

            // Rate Limiting routes (Advanced security features)
            Route::prefix("rate-limits")->middleware('feature:rate_limits')->group(function (): void {
                Route::get("/status/{action?}", [RateLimitController::class, "getStatus"]);
                Route::get("/all-status", [RateLimitController::class, "getAllStatus"]);
                Route::post("/reset/{action}", [RateLimitController::class, "reset"]);
                Route::get("/stats/{timeframe?}", [RateLimitController::class, "getStats"]);
                Route::get("/suspicious-activity", [RateLimitController::class, "checkSuspiciousActivity"]);
                Route::post("/cleanup", [RateLimitController::class, "cleanup"]);
            });

            // Event routes
            Route::prefix('events')->group(function () {
                Route::get('/', [\App\Http\Controllers\EventController::class, 'index']);
                Route::post('/', [\App\Http\Controllers\EventController::class, 'store']);
                Route::get('/my-events', [\App\Http\Controllers\EventController::class, 'myEvents']);
                Route::get('/{id}', [\App\Http\Controllers\EventController::class, 'show']);
                Route::post('/{id}/rsvp', [\App\Http\Controllers\EventController::class, 'rsvp']);
            });
    });
    
    // Moderation routes (Phase 2: Safety Features)
    // Define outside the general rate-limited group so that feature:moderation
    // executes before any advanced rate limiting middleware. This prevents
    // Redis access during tests when the moderation feature is disabled.
    Route::middleware(['auth.api', 'feature:moderation', 'auth.moderator'])->prefix('moderation')->group(function (): void {
        Route::get('/dashboard', [ModerationController::class, 'dashboard']);
        Route::get('/flagged-content', [ModerationController::class, 'flaggedContent']);
        Route::post('/flags/{artifactId}/review', [ModerationController::class, 'reviewFlag']);
        Route::get('/spoof-detections', [ModerationController::class, 'spoofDetections']);
        Route::post('/spoofs/{detectionId}/review', [ModerationController::class, 'reviewSpoof']);
        Route::get('/throttles', [ModerationController::class, 'activeThrottles']);
        Route::delete('/throttles/{throttleId}', [ModerationController::class, 'removeThrottle']);
        Route::get('/actions', [ModerationController::class, 'actionHistory']);
        Route::get('/users/{userId}', [ModerationController::class, 'userProfile']);
    });

    // Configuration routes (Admin only)
    Route::middleware(['auth.api', 'auth.moderator'])->prefix('config')->group(function (): void {
        Route::get('/features', [ConfigController::class, 'getFeatures']);
        Route::put('/features', [ConfigController::class, 'updateFeatures']);
        Route::get('/health', [ConfigController::class, 'getHealth']);
    });
});
