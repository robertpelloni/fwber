<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoScreenerClient
{
    protected $baseUrl;
    protected $timeout;

    public function __construct()
    {
        // Default to the Rust microservice port
        $this->baseUrl = env('GEO_SCREENER_URL', 'http://127.0.0.1:8080');
        $this->timeout = config('services.geo_screener.timeout', 2);
    }

    /**
     * Determine if the GeoScreener is enabled via env/config.
     */
    public function isEnabled(): bool
    {
        return env('GEO_SCREENER_ENABLED', false);
    }

    /**
     * Index a user's location into the Rust H3 grid.
     */
    public function indexLocation(int $userId, float $lat, float $lng): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/index", [
                    'user_id' => $userId,
                    'lat' => $lat,
                    'lng' => $lng,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("GeoScreener Index Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Quickly retrieve user IDs within a specified radius using H3 O(1) grid traversal.
     */
    public function getNearbyUsers(float $lat, float $lng, float $radiusMeters = 1000): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/nearby", [
                    'lat' => $lat,
                    'lng' => $lng,
                    'radius_m' => $radiusMeters,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['users'] ?? [];
            }
            
            return [];
        } catch (\Exception $e) {
            Log::warning("GeoScreener Nearby Error: " . $e->getMessage());
            return [];
        }
    }
}
