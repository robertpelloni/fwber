<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BulletinBoardController;
use App\Http\Controllers\ContentGenerationController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\MercureAuthController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RateLimitController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\WebSocketController;
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
        
        // Matching routes
        Route::get("/matches", [MatchController::class, "index"]);
        Route::post("/matches/action", [MatchController::class, "action"]);
        
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
        
        // Bulletin Board routes (Phase 5B - Location-Based Bulletin Board System)
        Route::get("/bulletin-boards", [BulletinBoardController::class, "index"]);
        Route::get("/bulletin-boards/{id}", [BulletinBoardController::class, "show"]);
        Route::post("/bulletin-boards", [BulletinBoardController::class, "createOrFind"]);
        Route::middleware('throttle:bulletin-message')->group(function () {
            Route::post("/bulletin-boards/{id}/messages", [BulletinBoardController::class, "postMessage"]);
        });
        Route::get("/bulletin-boards/{id}/messages", [BulletinBoardController::class, "getMessages"]);
        Route::get("/bulletin-boards/{id}/stream", [BulletinBoardController::class, "stream"]);
        
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
            Route::prefix("content-generation")->group(function (): void {
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
