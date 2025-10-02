<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware("api")->group(function (): void {
    Route::post("/auth/register", [AuthController::class, "register"]);
    Route::post("/auth/login", [AuthController::class, "login"]);

    Route::middleware("auth.api")->group(function (): void {
        Route::get("/user", [ProfileController::class, "show"]);
        Route::put("/user", [ProfileController::class, "update"]);
        Route::post("/auth/logout", [AuthController::class, "logout"]);
        Route::get("/matches", [MatchController::class, "index"]);
    });
});
