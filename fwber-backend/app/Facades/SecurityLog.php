<?php

namespace App\Facades;

use Illuminate\Support\Facades\Log;

class SecurityLog
{
    public static function authSuccess(array $context = []): void
    {
        Log::channel('security')->info('Authentication successful', $context);
    }
    
    public static function authFailed(array $context = []): void
    {
        Log::channel('security')->warning('Authentication failed', $context);
    }
    
    public static function authorizationFailed(array $context = []): void
    {
        Log::channel('security')->warning('Authorization failed', $context);
    }
    
    public static function rateLimitExceeded(array $context = []): void
    {
        Log::channel('security')->warning('Rate limit exceeded', $context);
    }
    
    public static function suspiciousActivity(array $context = []): void
    {
        Log::channel('security')->error('Suspicious activity detected', $context);
    }
    
    public static function tokenExpired(array $context = []): void
    {
        Log::channel('security')->info('Token expired', $context);
    }
}
