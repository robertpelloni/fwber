<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind FeatureFlagService as a singleton
        $this->app->singleton(\App\Services\FeatureFlagService::class, function ($app) {
            return new \App\Services\FeatureFlagService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure rate limiting for bulletin board messages
        RateLimiter::for('bulletin-message', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(10)->by('user:' . $request->user()->id) // 10 messages per minute for authenticated users
                : Limit::perMinute(5)->by('ip:' . $request->ip()); // 5 messages per minute for anonymous users
        });
        
        // Configure rate limiting for authentication
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by('ip:' . $request->ip()); // 5 login attempts per minute per IP
        });
        
        // Configure rate limiting for general API usage
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(100)->by('user:' . $request->user()->id) // 100 requests per minute for authenticated users
                : Limit::perMinute(60)->by('ip:' . $request->ip()); // 60 requests per minute for anonymous users
        });
        
        // Configure rate limiting for AI content generation (expensive operations)
        RateLimiter::for('content_generation', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(10)->by('user:' . $request->user()->id) // 10 generations per minute
                : Limit::perMinute(3)->by('ip:' . $request->ip()); // 3 generations per minute for guests
        });
        
        // Configure rate limiting for photo uploads
        RateLimiter::for('photo_uploads', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(20)->by('user:' . $request->user()->id) // 20 photo operations per minute
                : Limit::perMinute(5)->by('ip:' . $request->ip()); // 5 uploads per minute for guests
        });
    }
}
