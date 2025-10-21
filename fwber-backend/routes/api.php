<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware("api")->group(function (): void {
    Route::post("/auth/register", [AuthController::class, "register"]);
    Route::post("/auth/login", [AuthController::class, "login"]);

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
    });
});
