<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DeviceFingerprintingService
{
    private array $config;
    private array $suspiciousPatterns;

    public function __construct()
    {
        $this->config = config('device_fingerprinting', [
            'enabled' => true,
            'max_users_per_device' => 3,
            'device_block_duration' => 7200, // 2 hours
            'redis_prefix' => 'device_fingerprint',
            'suspicious_threshold' => 5,
        ]);

        $this->suspiciousPatterns = [
            'bot_user_agents' => [
                '/bot/i', '/crawler/i', '/spider/i', '/scraper/i',
                '/curl/i', '/wget/i', '/python/i', '/java/i',
            ],
            'suspicious_headers' => [
                'missing_user_agent' => true,
                'missing_accept_language' => true,
                'missing_accept_encoding' => true,
            ],
            'rapid_requests' => [
                'max_requests_per_minute' => 60,
                'max_requests_per_hour' => 1000,
            ],
        ];
    }

    /**
     * Generate device fingerprint from request
     */
    public function generateFingerprint(Request $request): string
    {
        $components = [
            'user_agent' => $request->userAgent() ?? '',
            'accept_language' => $request->header('Accept-Language', ''),
            'accept_encoding' => $request->header('Accept-Encoding', ''),
            'connection' => $request->header('Connection', ''),
            'dnt' => $request->header('DNT', ''),
            'accept' => $request->header('Accept', ''),
            'cache_control' => $request->header('Cache-Control', ''),
            'pragma' => $request->header('Pragma', ''),
        ];

        // Add IP-based components (hashed for privacy)
        $ip = $request->ip();
        $components['ip_hash'] = hash('sha256', $ip . config('app.key'));

        // Create fingerprint
        $fingerprint = hash('sha256', implode('|', $components));
        
        return $fingerprint;
    }

    /**
     * Check if device is suspicious
     */
    public function checkSuspiciousDevice(Request $request): array
    {
        $fingerprint = $this->generateFingerprint($request);
        $suspicious = false;
        $reasons = [];

        // Check user agent patterns
        $userAgent = $request->userAgent() ?? '';
        foreach ($this->suspiciousPatterns['bot_user_agents'] as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                $suspicious = true;
                $reasons[] = 'Bot-like user agent detected';
                break;
            }
        }

        // Check for missing headers
        if (empty($userAgent)) {
            $suspicious = true;
            $reasons[] = 'Missing user agent';
        }

        if (empty($request->header('Accept-Language'))) {
            $suspicious = true;
            $reasons[] = 'Missing Accept-Language header';
        }

        if (empty($request->header('Accept-Encoding'))) {
            $suspicious = true;
            $reasons[] = 'Missing Accept-Encoding header';
        }

        // Check request frequency
        $requestCount = $this->checkRequestFrequency($fingerprint);
        if ($requestCount > $this->suspiciousPatterns['rapid_requests']['max_requests_per_minute']) {
            $suspicious = true;
            $reasons[] = 'Excessive request frequency';
        }

        return [
            'suspicious' => $suspicious,
            'reasons' => $reasons,
            'fingerprint' => $fingerprint,
            'request_count' => $requestCount,
        ];
    }

    /**
     * Check for multiple users on same device
     */
    public function checkMultipleUsers(string $fingerprint, int $userId): array
    {
        $key = $this->buildKey($fingerprint);
        $associatedUsers = Redis::smembers($key);
        
        if (count($associatedUsers) > $this->config['max_users_per_device']) {
            return [
                'suspicious' => true,
                'reason' => 'Multiple accounts from same device',
                'associated_users' => $associatedUsers,
                'user_count' => count($associatedUsers),
            ];
        }

        return ['suspicious' => false];
    }

    /**
     * Associate user with device fingerprint
     */
    public function associateUser(string $fingerprint, int $userId): void
    {
        $key = $this->buildKey($fingerprint);
        Redis::sadd($key, $userId);
        Redis::expire($key, $this->config['device_block_duration']);
    }

    /**
     * Block suspicious device
     */
    public function blockDevice(string $fingerprint, string $reason): void
    {
        $blockKey = $this->buildBlockKey($fingerprint);
        Redis::setex($blockKey, $this->config['device_block_duration'], $reason);
        
        Log::warning('Device blocked', [
            'fingerprint' => $fingerprint,
            'reason' => $reason,
            'blocked_until' => now()->addSeconds($this->config['device_block_duration'])->toISOString(),
        ]);
    }

    /**
     * Check if device is blocked
     */
    public function isDeviceBlocked(string $fingerprint): array
    {
        $blockKey = $this->buildBlockKey($fingerprint);
        $blockReason = Redis::get($blockKey);
        
        if ($blockReason) {
            return [
                'blocked' => true,
                'reason' => $blockReason,
                'blocked_until' => now()->addSeconds(Redis::ttl($blockKey))->toISOString(),
            ];
        }

        return ['blocked' => false];
    }

    /**
     * Get device statistics
     */
    public function getDeviceStats(): array
    {
        $pattern = $this->config['redis_prefix'] . ':*';
        $keys = Redis::keys($pattern);
        
        $stats = [
            'total_devices' => count($keys),
            'blocked_devices' => 0,
            'suspicious_devices' => 0,
            'multi_user_devices' => 0,
        ];

        foreach ($keys as $key) {
            if (str_contains($key, ':blocked:')) {
                $stats['blocked_devices']++;
            } elseif (str_contains($key, ':suspicious:')) {
                $stats['suspicious_devices']++;
            } else {
                $userCount = Redis::scard($key);
                if ($userCount > 1) {
                    $stats['multi_user_devices']++;
                }
            }
        }

        return $stats;
    }

    /**
     * Clean up expired device data
     */
    public function cleanupExpiredDevices(): int
    {
        $pattern = $this->config['redis_prefix'] . ':*';
        $keys = Redis::keys($pattern);
        $cleaned = 0;

        foreach ($keys as $key) {
            $ttl = Redis::ttl($key);
            if ($ttl === -1) { // No expiration set
                Redis::expire($key, $this->config['device_block_duration']);
                $cleaned++;
            } elseif ($ttl === -2) { // Key doesn't exist
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Get device fingerprint analysis
     */
    public function analyzeFingerprint(string $fingerprint): array
    {
        $key = $this->buildKey($fingerprint);
        $associatedUsers = Redis::smembers($key);
        $requestCount = $this->checkRequestFrequency($fingerprint);
        
        return [
            'fingerprint' => $fingerprint,
            'associated_users' => $associatedUsers,
            'user_count' => count($associatedUsers),
            'request_count' => $requestCount,
            'is_suspicious' => count($associatedUsers) > $this->config['max_users_per_device'],
            'is_blocked' => $this->isDeviceBlocked($fingerprint)['blocked'],
        ];
    }

    /**
     * Check request frequency for device
     */
    private function checkRequestFrequency(string $fingerprint): int
    {
        $key = $this->buildRequestKey($fingerprint);
        $currentMinute = now()->format('Y-m-d H:i');
        $minuteKey = "{$key}:{$currentMinute}";
        
        $count = Redis::incr($minuteKey);
        Redis::expire($minuteKey, 60); // Expire after 1 minute
        
        return $count;
    }

    /**
     * Build Redis key for device fingerprint
     */
    private function buildKey(string $fingerprint): string
    {
        return "{$this->config['redis_prefix']}:{$fingerprint}";
    }

    /**
     * Build Redis key for blocked device
     */
    private function buildBlockKey(string $fingerprint): string
    {
        return "{$this->config['redis_prefix']}:blocked:{$fingerprint}";
    }

    /**
     * Build Redis key for request tracking
     */
    private function buildRequestKey(string $fingerprint): string
    {
        return "{$this->config['redis_prefix']}:requests:{$fingerprint}";
    }

    /**
     * Generate device fingerprint with additional security
     */
    public function generateSecureFingerprint(Request $request): string
    {
        $baseFingerprint = $this->generateFingerprint($request);
        
        // Add additional security components
        $securityComponents = [
            'ip_hash' => hash('sha256', $request->ip() . config('app.key')),
            'timestamp' => now()->format('Y-m-d H:i'),
            'session_id' => $request->session()->getId(),
        ];

        $secureFingerprint = hash('sha256', $baseFingerprint . implode('|', $securityComponents));
        
        return $secureFingerprint;
    }

    /**
     * Validate device fingerprint
     */
    public function validateFingerprint(string $fingerprint): bool
    {
        // Check if fingerprint is valid format (64 character hex string)
        if (!preg_match('/^[a-f0-9]{64}$/', $fingerprint)) {
            return false;
        }

        // Check if fingerprint is not in blocklist
        $blockKey = $this->buildBlockKey($fingerprint);
        return !Redis::exists($blockKey);
    }

    /**
     * Get device risk score
     */
    public function getDeviceRiskScore(string $fingerprint): float
    {
        $analysis = $this->analyzeFingerprint($fingerprint);
        $riskScore = 0.0;

        // Risk factors
        if ($analysis['user_count'] > 1) {
            $riskScore += 0.3 * ($analysis['user_count'] - 1);
        }

        if ($analysis['request_count'] > 100) {
            $riskScore += 0.2;
        }

        if ($analysis['is_suspicious']) {
            $riskScore += 0.4;
        }

        if ($analysis['is_blocked']) {
            $riskScore += 0.5;
        }

        return min(1.0, $riskScore);
    }
}
