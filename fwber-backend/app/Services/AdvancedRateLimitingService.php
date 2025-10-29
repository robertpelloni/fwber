<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AdvancedRateLimitingService
{
    private array $config;
    private array $actionConfigs;

    public function __construct()
    {
        $this->config = config('rate_limiting', [
            'default_capacity' => 100,
            'default_refill_rate' => 10,
            'redis_prefix' => 'rate_limit',
            'cleanup_interval' => 3600, // 1 hour
        ]);

        $this->actionConfigs = [
            'content_generation' => [
                'capacity' => 10,
                'refill_rate' => 1,
                'cost_per_request' => 1,
                'burst_allowance' => 5,
            ],
            'bulletin_post' => [
                'capacity' => 20,
                'refill_rate' => 2,
                'cost_per_request' => 1,
                'burst_allowance' => 10,
            ],
            'location_update' => [
                'capacity' => 50,
                'refill_rate' => 5,
                'cost_per_request' => 1,
                'burst_allowance' => 25,
            ],
            'photo_upload' => [
                'capacity' => 15,
                'refill_rate' => 1,
                'cost_per_request' => 2,
                'burst_allowance' => 5,
            ],
            'api_call' => [
                'capacity' => 1000,
                'refill_rate' => 100,
                'cost_per_request' => 1,
                'burst_allowance' => 200,
            ],
        ];
    }

    /**
     * Check if user is rate limited for a specific action
     */
    public function checkRateLimit(string $userId, string $action, array $context = []): array
    {
        $config = $this->getActionConfig($action);
        $key = $this->buildKey($userId, $action);
        
        $bucket = $this->getTokenBucket($key, $config);
        $cost = $this->calculateCost($action, $context);
        
        if ($bucket['tokens'] < $cost) {
            $this->logRateLimit($userId, $action, $context);
            return [
                'allowed' => false,
                'retry_after' => $this->calculateRetryAfter($bucket, $config, $cost),
                'remaining_tokens' => $bucket['tokens'],
                'reset_time' => $bucket['reset_time'],
                'reason' => 'Rate limit exceeded'
            ];
        }

        $this->consumeTokens($key, $bucket, $cost, $config);
        
        return [
            'allowed' => true,
            'remaining_tokens' => $bucket['tokens'] - $cost,
            'reset_time' => $bucket['reset_time'],
        ];
    }

    /**
     * Get current rate limit status for user
     */
    public function getRateLimitStatus(string $userId, string $action): array
    {
        $config = $this->getActionConfig($action);
        $key = $this->buildKey($userId, $action);
        $bucket = $this->getTokenBucket($key, $config);

        return [
            'tokens_remaining' => $bucket['tokens'],
            'capacity' => $config['capacity'],
            'refill_rate' => $config['refill_rate'],
            'reset_time' => $bucket['reset_time'],
            'percentage_used' => round((($config['capacity'] - $bucket['tokens']) / $config['capacity']) * 100, 2),
        ];
    }

    /**
     * Reset rate limit for user (admin function)
     */
    public function resetRateLimit(string $userId, string $action): bool
    {
        $key = $this->buildKey($userId, $action);
        return Redis::del($key) > 0;
    }

    /**
     * Get rate limit statistics
     */
    public function getRateLimitStats(string $timeframe = '1h'): array
    {
        $pattern = $this->config['redis_prefix'] . ':*';
        $keys = Redis::keys($pattern);
        
        $stats = [
            'total_keys' => count($keys),
            'timeframe' => $timeframe,
            'actions' => [],
        ];

        foreach ($this->actionConfigs as $action => $config) {
            $actionKeys = array_filter($keys, fn($key) => str_contains($key, ":{$action}:"));
            $stats['actions'][$action] = [
                'active_buckets' => count($actionKeys),
                'config' => $config,
            ];
        }

        return $stats;
    }

    /**
     * Clean up expired rate limit entries
     */
    public function cleanupExpiredEntries(): int
    {
        $pattern = $this->config['redis_prefix'] . ':*';
        $keys = Redis::keys($pattern);
        $cleaned = 0;

        foreach ($keys as $key) {
            $ttl = Redis::ttl($key);
            if ($ttl === -1) { // No expiration set
                Redis::expire($key, $this->config['cleanup_interval']);
                $cleaned++;
            } elseif ($ttl === -2) { // Key doesn't exist
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Get token bucket for user/action
     */
    private function getTokenBucket(string $key, array $config): array
    {
        $data = Redis::hgetall($key);
        $now = time();
        $lastRefill = $data['last_refill'] ?? $now;
        $tokens = (float) ($data['tokens'] ?? $config['capacity']);

        // Calculate tokens to add based on time passed
        $timePassed = max(0, $now - $lastRefill);
        $tokensToAdd = $timePassed * $config['refill_rate'];
        
        // Cap tokens at capacity
        $newTokens = min($config['capacity'], $tokens + $tokensToAdd);
        
        // Calculate reset time
        $resetTime = $now + (($config['capacity'] - $newTokens) / $config['refill_rate']);

        return [
            'tokens' => $newTokens,
            'last_refill' => $now,
            'reset_time' => $resetTime,
        ];
    }

    /**
     * Consume tokens from bucket
     */
    private function consumeTokens(string $key, array $bucket, int $cost, array $config): void
    {
        $newTokens = max(0, $bucket['tokens'] - $cost);
        
        Redis::hmset($key, [
            'tokens' => $newTokens,
            'last_refill' => $bucket['last_refill'],
        ]);

        // Set expiration
        Redis::expire($key, $this->config['cleanup_interval']);
    }

    /**
     * Calculate cost for action based on context
     */
    private function calculateCost(string $action, array $context): int
    {
        $config = $this->getActionConfig($action);
        $baseCost = $config['cost_per_request'];

        // Adjust cost based on context
        if ($action === 'content_generation') {
            $contentLength = strlen($context['content'] ?? '');
            if ($contentLength > 1000) {
                $baseCost += 1; // Higher cost for longer content
            }
        }

        if ($action === 'photo_upload') {
            $fileSize = $context['file_size'] ?? 0;
            if ($fileSize > 5 * 1024 * 1024) { // 5MB
                $baseCost += 1; // Higher cost for larger files
            }
        }

        return $baseCost;
    }

    /**
     * Calculate retry after time
     */
    private function calculateRetryAfter(array $bucket, array $config, int $cost): int
    {
        $tokensNeeded = $cost - $bucket['tokens'];
        return (int) ceil($tokensNeeded / $config['refill_rate']);
    }

    /**
     * Get action configuration
     */
    private function getActionConfig(string $action): array
    {
        return $this->actionConfigs[$action] ?? [
            'capacity' => $this->config['default_capacity'],
            'refill_rate' => $this->config['default_refill_rate'],
            'cost_per_request' => 1,
            'burst_allowance' => 10,
        ];
    }

    /**
     * Build Redis key for user/action
     */
    private function buildKey(string $userId, string $action): string
    {
        return "{$this->config['redis_prefix']}:{$userId}:{$action}";
    }

    /**
     * Log rate limit events
     */
    private function logRateLimit(string $userId, string $action, array $context): void
    {
        Log::warning('Rate limit exceeded', [
            'user_id' => $userId,
            'action' => $action,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Check for suspicious rate limit patterns
     */
    public function detectSuspiciousActivity(string $userId): array
    {
        $pattern = $this->config['redis_prefix'] . ":{$userId}:*";
        $keys = Redis::keys($pattern);
        
        $suspicious = false;
        $reasons = [];

        // Check for multiple action types being rate limited
        $rateLimitedActions = [];
        foreach ($keys as $key) {
            $action = explode(':', $key)[2] ?? '';
            $bucket = $this->getTokenBucket($key, $this->getActionConfig($action));
            if ($bucket['tokens'] < 1) {
                $rateLimitedActions[] = $action;
            }
        }

        if (count($rateLimitedActions) > 3) {
            $suspicious = true;
            $reasons[] = 'Multiple actions rate limited simultaneously';
        }

        // Check for rapid successive rate limit hits
        $recentHits = Cache::get("rate_limit_hits:{$userId}", []);
        $recentHits[] = now()->timestamp;
        $recentHits = array_slice($recentHits, -10); // Keep last 10 hits
        
        if (count($recentHits) >= 10) {
            $timeSpan = $recentHits[9] - $recentHits[0];
            if ($timeSpan < 60) { // 10 hits in less than 1 minute
                $suspicious = true;
                $reasons[] = 'Rapid successive rate limit hits';
            }
        }

        Cache::put("rate_limit_hits:{$userId}", $recentHits, 300); // 5 minutes

        return [
            'suspicious' => $suspicious,
            'reasons' => $reasons,
            'rate_limited_actions' => $rateLimitedActions,
        ];
    }
}
