<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoScreenerService
{
    protected bool $enabled;

    protected string $baseUrl;

    protected int $timeout;

    public function __construct()
    {
        $this->enabled = Config::get('services.geo_screener.enabled', false);
        $this->baseUrl = rtrim(Config::get('services.geo_screener.url', 'http://127.0.0.1:8081'), '/');
        $this->timeout = Config::get('services.geo_screener.timeout', 2);
    }

    /**
     * Index a user's location in the Rust H3 spatial index.
     */
    public function indexLocation(int $userId, float $lat, float $lng): bool
    {
        if (! $this->enabled) {
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
            Log::warning("GeoScreener: Failed to index location for user {$userId}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Get nearby user IDs from the Rust H3 spatial index.
     *
     * @return array|null List of user IDs or null if service failed/disabled
     */
    public function getNearbyUserIds(float $lat, float $lng, float $radiusMeters): ?array
    {
        if (! $this->enabled) {
            return null;
        }

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
}
