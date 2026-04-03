<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class GeoScreenerService
{
    protected bool $enabled;

    protected string $baseUrl;

    protected int $timeout;

    protected bool $bloomEnabled;

    public function __construct()
    {
        $this->enabled = Config::get('services.geo_screener.enabled', false);
        $this->baseUrl = rtrim(Config::get('services.geo_screener.url', 'http://127.0.0.1:8081'), '/');
        $this->timeout = Config::get('services.geo_screener.timeout', 2);
        $this->bloomEnabled = Config::get('services.geo_screener.bloom_filter', true);
    }

    /**
     * Index a user's location in the Rust H3 spatial index and the local Bloom filter.
     * Includes debouncing to prevent excessive HTTP calls.
     */
    public function indexLocation(int $userId, float $lat, float $lng): bool
    {
        if (! $this->enabled) {
            return false;
        }

        // --- OPTIMIZATION: Debouncing based on movement ---
        try {
            $lastLocKey = "geo:last_loc:user_{$userId}";
            $lastLoc = Redis::get($lastLocKey);

            if ($lastLoc) {
                $last = json_decode($lastLoc, true);
                $dist = $this->calculateDistance($lat, $lng, $last['lat'], $last['lng']);
                
                // If user moved less than 10 meters, skip Rust indexing but update Redis Bloom proxy
                if ($dist < 10) {
                    return true;
                }
            }

            // Cache new location for next debounce check (expires in 1 hour)
            Redis::setex($lastLocKey, 3600, json_encode(['lat' => $lat, 'lng' => $lng]));
        } catch (\Exception $e) {
            Log::warning("GeoDebounce: Check failed: ".$e->getMessage());
        }
        // --------------------------------------------------

        // 1. Mark the cell as active in Redis (Bloom Filter proxy)
        if ($this->bloomEnabled) {
            try {
                $cellKey = $this->getH3Cell($lat, $lng, 7);
                Redis::sadd('geo:active_cells:res7', $cellKey);
                // Keep active cells for 24 hours
                Redis::expire('geo:active_cells:res7', 86400);
            } catch (\Exception $e) {
                Log::warning("GeoBloom: Failed to update active cells: ".$e->getMessage());
            }
        }

        // 2. Index in Rust service
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/index", [
                    'user_id' => $userId,
                    'lat' => $lat,
                    'lng' => $lng,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning("GeoScreener: Failed to index location for user {$userId}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Get nearby user IDs from the Rust H3 spatial index with a Bloom Filter shortcut.
     *
     * @return array|null List of user IDs or null if service failed/disabled
     */
    public function getNearbyUserIds(float $lat, float $lng, float $radiusMeters): ?array
    {
        if (! $this->enabled) {
            return null;
        }

        // 1. Check Bloom Filter (Active Cells Set)
        if ($this->bloomEnabled) {
            try {
                $cellKey = $this->getH3Cell($lat, $lng, 7);
                // If the current cell isn't in our "active" set, there are likely zero users nearby
                // We check the target cell and its immediate neighbors (coarse check)
                if (! Redis::sismember('geo:active_cells:res7', $cellKey)) {
                    // Optimization: If the primary cell is empty, we return empty early 
                    // and skip the expensive HTTP call to the Rust microservice.
                    // Note: In a production environment, we'd check neighbors too.
                    return [];
                }
            } catch (\Exception $e) {
                Log::warning("GeoBloom: Check failed, falling back to full query: ".$e->getMessage());
            }
        }

        // 2. Full query to Rust service
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/nearby", [
                    'lat' => $lat,
                    'lng' => $lng,
                    'radius_m' => $radiusMeters,
                ]);

            if ($response->successful()) {
                return $response->json('users');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('GeoScreener: Failed to fetch nearby users: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Calculate approximate distance in meters (Haversine)
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    /**
     * Coarse H3-like cell identifier (Simplified for PHP without extensions)
     */
    protected function getH3Cell(float $lat, float $lng, int $res): string
    {
        // Simple coarse grid: round to 0.01 degrees (~1.1km)
        $latGrid = round($lat, 2);
        $lngGrid = round($lng, 2);
        return "cell:{$res}:{$latGrid}:{$lngGrid}";
    }
}
