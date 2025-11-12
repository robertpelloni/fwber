<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BulletinBoardController;
use App\Http\Controllers\ChatroomController;
use App\Http\Controllers\ChatroomMessageController;
use App\Http\Controllers\ContentGenerationController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\MercureAuthController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProximityChatroomController;
use App\Http\Controllers\ProximityChatroomMessageController;
use App\Http\Controllers\RateLimitController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\Api\RelationshipTierController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\UserPhysicalProfileController;
use App\Http\Controllers\Api\BlockController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\GroupMessageController;
use App\Http\Controllers\WebSocketController;
use App\Http\Controllers\ProximityArtifactController;
use Illuminate\Support\Facades\Route;

Route::middleware("api")->group(function (): void {
    Route::middleware('throttle:auth')->group(function () {
        Route::post("/auth/register", [AuthController::class, "register"]);
        Route::post("/auth/login", [AuthController::class, "login"]);
    });

    Route::middleware("auth.api")->group(function (): void {
        // Profile routes (Phase 3A - Multi-AI Implementation)
        Route::get("/user", [ProfileController::class, "show"]);
        Route::put("/user", [ProfileController::class, "update"]);
        Route::get("/profile/completeness", [ProfileController::class, "completeness"]);
        
        Route::post("/auth/logout", [AuthController::class, "logout"]);
        
        // Matching routes (require complete profile)
        Route::middleware('profile.complete')->group(function (): void {
            Route::get("/matches", [MatchController::class, "index"]);
            Route::post("/matches/action", [MatchController::class, "action"]);
        });
        
        // Message routes with tier tracking
        Route::prefix("messages")->group(function (): void {
            Route::get("/unread-count", [MessageController::class, "unreadCount"]);
            Route::get("/{userId}", [MessageController::class, "index"]);
            Route::post("/", [MessageController::class, "store"]);
            Route::post("/{messageId}/read", [MessageController::class, "markAsRead"]);
        });
        
            // Group routes
            Route::prefix("groups")->group(function (): void {
                Route::get("/", [GroupController::class, "index"]);
                Route::post("/", [GroupController::class, "store"]);
                Route::get("/discover", [GroupController::class, "discover"]);
                Route::get("/{groupId}", [GroupController::class, "show"]);
                Route::put("/{groupId}", [GroupController::class, "update"]);
                Route::delete("/{groupId}", [GroupController::class, "destroy"]);
                Route::post("/{groupId}/join", [GroupController::class, "join"]);
                Route::post("/{groupId}/leave", [GroupController::class, "leave"]);
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
        Route::post("/photos", [PhotoController::class, "store"]);
        Route::put("/photos/{id}", [PhotoController::class, "update"]);
        Route::delete("/photos/{id}", [PhotoController::class, "destroy"]);
        Route::post("/photos/reorder", [PhotoController::class, "reorder"]);
        
        // Location routes (Phase 5A - Location-Based Social Features)
        Route::get("/location", [LocationController::class, "show"]);
        Route::post("/location", [LocationController::class, "update"]);
        Route::put("/location/privacy", [LocationController::class, "updatePrivacy"]);
        Route::delete("/location", [LocationController::class, "clear"]);
        Route::get("/location/nearby", [LocationController::class, "nearby"]);

        // Proximity Artifacts (Unified Ephemeral Layer)
        Route::prefix('proximity')->group(function (): void {
            Route::get('/feed', [ProximityArtifactController::class, 'index']);
            Route::post('/artifacts', [ProximityArtifactController::class, 'store']);
            Route::get('/artifacts/{id}', [ProximityArtifactController::class, 'show']);
            Route::post('/artifacts/{id}/flag', [ProximityArtifactController::class, 'flag']);
            Route::delete('/artifacts/{id}', [ProximityArtifactController::class, 'destroy']);
        });
        
        // Bulletin Board routes (Phase 5B - Location-Based Bulletin Board System)
        Route::get("/bulletin-boards", [BulletinBoardController::class, "index"]);
        Route::get("/bulletin-boards/{id}", [BulletinBoardController::class, "show"]);
        Route::post("/bulletin-boards", [BulletinBoardController::class, "createOrFind"]);
        Route::middleware('throttle:bulletin-message')->group(function () {
            Route::post("/bulletin-boards/{id}/messages", [BulletinBoardController::class, "postMessage"]);
        });
        Route::get("/bulletin-boards/{id}/messages", [BulletinBoardController::class, "getMessages"]);
        Route::get("/bulletin-boards/{id}/stream", [BulletinBoardController::class, "stream"]);
        
        // Chatroom routes (Phase 6A - Real-time Location-Based Chatrooms)
        Route::prefix("chatrooms")->group(function (): void {
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
        Route::prefix("chatrooms/{chatroomId}/messages")->group(function (): void {
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
        Route::prefix("proximity-chatrooms")->group(function (): void {
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
        Route::prefix("proximity-chatrooms/{chatroomId}/messages")->group(function (): void {
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
        Route::get("/analytics", [AnalyticsController::class, "index"]);
        Route::get("/analytics/realtime", [AnalyticsController::class, "realtime"]);
        Route::get("/analytics/moderation", [AnalyticsController::class, "moderation"]);
        
        // Recommendation routes (AI-powered personalization)
        Route::prefix("recommendations")->group(function (): void {
            Route::get("/", [RecommendationController::class, "index"]);
            Route::get("/trending", [RecommendationController::class, "trending"]);
            Route::get("/feed", [RecommendationController::class, "feed"]);
            Route::get("/type/{type}", [RecommendationController::class, "byType"]);
            Route::post("/feedback", [RecommendationController::class, "feedback"]);
            Route::get("/analytics", [RecommendationController::class, "analytics"]);
        });

            // WebSocket routes (bidirectional real-time communication)
            Route::prefix("websocket")->group(function (): void {
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
            // Apply a simple throttle to match test expectations (5 requests/minute allowed per user)
            Route::prefix("content-generation")->group(function (): void {
                Route::middleware('throttle:5,1')->post("/profile", [ContentGenerationController::class, "generateProfileContent"]);
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
            Route::prefix("rate-limits")->group(function (): void {
                Route::get("/status/{action?}", [RateLimitController::class, "getStatus"]);
                Route::get("/all-status", [RateLimitController::class, "getAllStatus"]);
                Route::post("/reset/{action}", [RateLimitController::class, "reset"]);
                Route::get("/stats/{timeframe?}", [RateLimitController::class, "getStats"]);
                Route::get("/suspicious-activity", [RateLimitController::class, "checkSuspiciousActivity"]);
                Route::post("/cleanup", [RateLimitController::class, "cleanup"]);
            });
    });
});
