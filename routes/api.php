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

    // Restored Resources
    Route::apiResource('venues', \App\Http\Controllers\VenueController::class);
    Route::apiResource('subscriptions', \App\Http\Controllers\SubscriptionController::class);
    Route::apiResource('event-matches', \App\Http\Controllers\EventMatchController::class);
    Route::apiResource('attendees', \App\Http\Controllers\AttendeeController::class);
});
