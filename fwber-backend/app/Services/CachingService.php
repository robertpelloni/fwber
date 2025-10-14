<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class CachingService
{
    private $redis;
    private $defaultTtl = 3600; // 1 hour

    public function __construct()
    {
        $this->redis = Redis::connection();
    }

    public function cacheUserProfile(int $userId, array $profileData, int $ttl = null): void
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $key = "user_profile:{$userId}";
        
        Cache::put($key, $profileData, $ttl);
        
        // Also store in Redis for real-time updates
        $this->redis->setex($key, $ttl, json_encode($profileData));
    }

    public function getUserProfile(int $userId): ?array
    {
        $key = "user_profile:{$userId}";
        
        // Try cache first
        $cached = Cache::get($key);
        if ($cached) {
            return $cached;
        }
        
        // Try Redis
        $redisData = $this->redis->get($key);
        if ($redisData) {
            $data = json_decode($redisData, true);
            Cache::put($key, $data, $this->defaultTtl);
            return $data;
        }
        
        return null;
    }

    public function cacheMatches(int $userId, array $matches, int $ttl = null): void
    {
        $ttl = $ttl ?? 300; // 5 minutes for matches
        $key = "user_matches:{$userId}";
        
        Cache::put($key, $matches, $ttl);
    }

    public function getCachedMatches(int $userId): ?array
    {
        $key = "user_matches:{$userId}";
        return Cache::get($key);
    }

    public function cacheMatchScore(int $userId1, int $userId2, float $score, int $ttl = null): void
    {
        $ttl = $ttl ?? 1800; // 30 minutes
        $key = "match_score:{$userId1}:{$userId2}";
        
        Cache::put($key, $score, $ttl);
    }

    public function getCachedMatchScore(int $userId1, int $userId2): ?float
    {
        $key = "match_score:{$userId1}:{$userId2}";
        return Cache::get($key);
    }

    public function cacheUserPresence(int $userId, array $presenceData, int $ttl = null): void
    {
        $ttl = $ttl ?? 300; // 5 minutes
        $key = "user_presence:{$userId}";
        
        $this->redis->setex($key, $ttl, json_encode($presenceData));
    }

    public function getUserPresence(int $userId): ?array
    {
        $key = "user_presence:{$userId}";
        $data = $this->redis->get($key);
        
        return $data ? json_decode($data, true) : null;
    }

    public function cacheMessage(int $messageId, array $messageData, int $ttl = null): void
    {
        $ttl = $ttl ?? 7200; // 2 hours
        $key = "message:{$messageId}";
        
        Cache::put($key, $messageData, $ttl);
    }

    public function getCachedMessage(int $messageId): ?array
    {
        $key = "message:{$messageId}";
        return Cache::get($key);
    }

    public function cacheConversation(int $userId1, int $userId2, array $messages, int $ttl = null): void
    {
        $ttl = $ttl ?? 1800; // 30 minutes
        $key = "conversation:{$userId1}:{$userId2}";
        
        Cache::put($key, $messages, $ttl);
    }

    public function getCachedConversation(int $userId1, int $userId2): ?array
    {
        $key = "conversation:{$userId1}:{$userId2}";
        return Cache::get($key);
    }

    public function cacheSearchResults(string $query, array $results, int $ttl = null): void
    {
        $ttl = $ttl ?? 600; // 10 minutes
        $key = "search:" . md5($query);
        
        Cache::put($key, $results, $ttl);
    }

    public function getCachedSearchResults(string $query): ?array
    {
        $key = "search:" . md5($query);
        return Cache::get($key);
    }

    public function cacheUserStats(int $userId, array $stats, int $ttl = null): void
    {
        $ttl = $ttl ?? 3600; // 1 hour
        $key = "user_stats:{$userId}";
        
        Cache::put($key, $stats, $ttl);
    }

    public function getCachedUserStats(int $userId): ?array
    {
        $key = "user_stats:{$userId}";
        return Cache::get($key);
    }

    public function invalidateUserCache(int $userId): void
    {
        $patterns = [
            "user_profile:{$userId}",
            "user_matches:{$userId}",
            "user_presence:{$userId}",
            "user_stats:{$userId}",
            "match_score:{$userId}:*",
            "conversation:{$userId}:*",
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                // Use Redis SCAN for pattern matching
                $keys = $this->redis->keys($pattern);
                if (!empty($keys)) {
                    $this->redis->del($keys);
                }
            } else {
                Cache::forget($pattern);
                $this->redis->del($pattern);
            }
        }
    }

    public function invalidateMatchCache(int $userId1, int $userId2): void
    {
        $keys = [
            "match_score:{$userId1}:{$userId2}",
            "match_score:{$userId2}:{$userId1}",
            "conversation:{$userId1}:{$userId2}",
            "conversation:{$userId2}:{$userId1}",
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
            $this->redis->del($key);
        }
    }

    public function warmupCache(): void
    {
        Log::info('Starting cache warmup...');
        
        // Warm up popular user profiles
        $popularUsers = $this->getPopularUsers();
        foreach ($popularUsers as $userId) {
            $this->warmupUserProfile($userId);
        }
        
        // Warm up active matches
        $activeMatches = $this->getActiveMatches();
        foreach ($activeMatches as $match) {
            $this->warmupMatchData($match['user1_id'], $match['user2_id']);
        }
        
        Log::info('Cache warmup completed');
    }

    private function getPopularUsers(): array
    {
        // In a real implementation, this would query the database
        // for users with the most profile views, messages, etc.
        return [1, 2, 3, 4, 5]; // Placeholder
    }

    private function getActiveMatches(): array
    {
        // In a real implementation, this would query the database
        // for recent matches
        return [
            ['user1_id' => 1, 'user2_id' => 2],
            ['user1_id' => 3, 'user2_id' => 4],
        ]; // Placeholder
    }

    private function warmupUserProfile(int $userId): void
    {
        // Load user profile data and cache it
        $user = \App\Models\User::with('profile')->find($userId);
        if ($user && $user->profile) {
            $profileData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile' => $user->profile->toArray(),
            ];
            
            $this->cacheUserProfile($userId, $profileData);
        }
    }

    private function warmupMatchData(int $userId1, int $userId2): void
    {
        // Load match data and cache it
        $user1 = \App\Models\User::with('profile')->find($userId1);
        $user2 = \App\Models\User::with('profile')->find($userId2);
        
        if ($user1 && $user2 && $user1->profile && $user2->profile) {
            // Calculate and cache match score
            $score = $this->calculateMatchScore($user1->profile, $user2->profile);
            $this->cacheMatchScore($userId1, $userId2, $score);
        }
    }

    private function calculateMatchScore($profile1, $profile2): float
    {
        // Simple scoring algorithm
        $score = 0;
        
        // Age compatibility
        if ($profile1->date_of_birth && $profile2->date_of_birth) {
            $age1 = $profile1->date_of_birth->diffInYears(now());
            $age2 = $profile2->date_of_birth->diffInYears(now());
            $ageDiff = abs($age1 - $age2);
            $score += max(0, 20 - $ageDiff);
        }
        
        // Gender compatibility
        if ($profile1->gender === $profile2->gender) {
            $score += 25;
        }
        
        // Location compatibility
        if ($profile1->location_latitude && $profile2->location_latitude) {
            $distance = $this->calculateDistance($profile1, $profile2);
            $score += max(0, 20 - ($distance / 5));
        }
        
        return min(100, $score);
    }

    private function calculateDistance($profile1, $profile2): float
    {
        if (!$profile1->location_latitude || !$profile2->location_latitude) {
            return 0;
        }

        $lat1 = $profile1->location_latitude;
        $lon1 = $profile1->location_longitude;
        $lat2 = $profile2->location_latitude;
        $lon2 = $profile2->location_longitude;

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + 
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        return $miles;
    }

    public function getCacheStats(): array
    {
        $redisInfo = $this->redis->info();
        
        return [
            'redis_memory_used' => $redisInfo['used_memory_human'] ?? 'N/A',
            'redis_connected_clients' => $redisInfo['connected_clients'] ?? 0,
            'redis_total_commands_processed' => $redisInfo['total_commands_processed'] ?? 0,
            'cache_hit_rate' => $this->calculateCacheHitRate(),
            'total_keys' => $this->redis->dbsize(),
        ];
    }

    private function calculateCacheHitRate(): float
    {
        $redisInfo = $this->redis->info();
        $hits = $redisInfo['keyspace_hits'] ?? 0;
        $misses = $redisInfo['keyspace_misses'] ?? 0;
        
        if ($hits + $misses === 0) {
            return 0;
        }
        
        return ($hits / ($hits + $misses)) * 100;
    }

    public function clearExpiredCache(): void
    {
        Log::info('Clearing expired cache entries...');
        
        // Redis automatically expires keys, but we can clean up any orphaned keys
        $keys = $this->redis->keys('*');
        $cleared = 0;
        
        foreach ($keys as $key) {
            $ttl = $this->redis->ttl($key);
            if ($ttl === -1) { // Key exists but has no expiration
                $this->redis->del($key);
                $cleared++;
            }
        }
        
        Log::info("Cleared {$cleared} orphaned cache entries");
    }
}
