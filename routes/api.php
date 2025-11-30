<?php

use Illuminate\Http\Request;
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

Route::post('auth/register', [\App\Http\Controllers\AuthController::class, 'register']);
Route::post('auth/login', [\App\Http\Controllers\AuthController::class, 'login']);

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
    // Auth
    Route::post('auth/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
    Route::get('auth/me', [\App\Http\Controllers\AuthController::class, 'me']);

    // Events
    Route::get('events/my-events', [EventController::class, 'myEvents']);
    Route::apiResource('events', EventController::class);
    Route::post('events/{id}/rsvp', [EventController::class, 'rsvp']);

    // Groups
    Route::get('groups/my-groups', [\App\Http\Controllers\GroupController::class, 'myGroups']);
    Route::post('groups/{id}/join', [\App\Http\Controllers\GroupController::class, 'join']);
    Route::post('groups/{id}/leave', [\App\Http\Controllers\GroupController::class, 'leave']);
    Route::apiResource('groups', \App\Http\Controllers\GroupController::class);

    // Premium
    Route::get('premium/who-likes-you', [\App\Http\Controllers\PremiumController::class, 'getWhoLikesYou']);
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
    Route::post('matches/action', [\App\Http\Controllers\MatchController::class, 'action']);

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
    Route::post('proximity-chatrooms/{chatroomId}/messages', [\App\Http\Controllers\ProximityChatroomMessageController::class, 'store']);
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
    Route::apiResource('subscriptions', \App\Http\Controllers\SubscriptionController::class);
    Route::apiResource('event-matches', \App\Http\Controllers\EventMatchController::class);
    Route::apiResource('attendees', \App\Http\Controllers\AttendeeController::class);

    // Venue Check-ins
    Route::post('venues/{id}/checkin', [\App\Http\Controllers\VenueCheckinController::class, 'store']);
    Route::post('venues/{id}/checkout', [\App\Http\Controllers\VenueCheckinController::class, 'destroy']);
    Route::get('venues/{id}/checkins', [\App\Http\Controllers\VenueCheckinController::class, 'index']);
    Route::get('user/checkin', [\App\Http\Controllers\VenueCheckinController::class, 'current']);
});
