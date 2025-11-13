<?php

namespace App\Services;

use App\Models\GeoSpoofDetection;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GeoSpoofDetectionService
{
    /**
     * Detect potential geolocation spoofing.
     *
     * @param int $userId
     * @param float $latitude
     * @param float $longitude
     * @param string $ipAddress
     * @return GeoSpoofDetection|null
     */
    public function detectSpoof(int $userId, float $latitude, float $longitude, string $ipAddress): ?GeoSpoofDetection
    {
        $detectionFlags = [];
        $suspicionScore = 0;

        // Get IP geolocation
        $ipGeo = $this->getIpGeolocation($ipAddress);
        
        if (!$ipGeo) {
            // Cannot verify without IP geolocation
            return null;
        }

        $ipLat = $ipGeo['latitude'];
        $ipLon = $ipGeo['longitude'];

        // Calculate distance between claimed location and IP location
        $distanceKm = $this->calculateDistance($latitude, $longitude, $ipLat, $ipLon);

        // Flag: Large distance from IP location
        if ($distanceKm > 500) {
            $detectionFlags[] = 'ip_distance_extreme';
            $suspicionScore += 40;
        } elseif ($distanceKm > 200) {
            $detectionFlags[] = 'ip_distance_high';
            $suspicionScore += 25;
        } elseif ($distanceKm > 100) {
            $detectionFlags[] = 'ip_distance_moderate';
            $suspicionScore += 15;
        }

        // Check for impossible velocity (teleportation)
        $velocityKmh = $this->checkImpossibleVelocity($userId, $latitude, $longitude);
        
        if ($velocityKmh !== null) {
            if ($velocityKmh > 1000) {
                $detectionFlags[] = 'impossible_velocity';
                $suspicionScore += 50;
            } elseif ($velocityKmh > 500) {
                $detectionFlags[] = 'suspicious_velocity';
                $suspicionScore += 30;
            } elseif ($velocityKmh > 200) {
                $detectionFlags[] = 'high_velocity';
                $suspicionScore += 15;
            }
        }

        // Check for VPN/Proxy indicators
        if ($this->isVpnOrProxy($ipAddress)) {
            $detectionFlags[] = 'vpn_or_proxy';
            $suspicionScore += 20;
        }

        // Check for data center IP
        if ($this->isDataCenterIp($ipAddress)) {
            $detectionFlags[] = 'datacenter_ip';
            $suspicionScore += 15;
        }

        // Check for frequent location changes
        if ($this->hasFrequentLocationChanges($userId)) {
            $detectionFlags[] = 'frequent_location_changes';
            $suspicionScore += 10;
        }

        // Only create detection record if suspicion score is significant
        if ($suspicionScore >= 25) {
            return GeoSpoofDetection::create([
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'ip_latitude' => $ipLat,
                'ip_longitude' => $ipLon,
                'distance_km' => (int) $distanceKm,
                'velocity_kmh' => $velocityKmh,
                'suspicion_score' => min(100, $suspicionScore),
                'detection_flags' => $detectionFlags,
                'is_confirmed_spoof' => false,
                'detected_at' => now(),
            ]);
        }

        return null;
    }

    /**
     * Get IP geolocation data (cached for performance).
     */
    private function getIpGeolocation(string $ipAddress): ?array
    {
        // Skip localhost/private IPs
        if ($this->isPrivateIp($ipAddress)) {
            return null;
        }

        $cacheKey = "ip_geo:{$ipAddress}";
        
        return Cache::remember($cacheKey, 3600, function () use ($ipAddress) {
            try {
                // Using ip-api.com (free tier, 45 req/min)
                // In production, use a paid service like MaxMind, IPinfo, or IP2Location
                $response = Http::timeout(5)->get("http://ip-api.com/json/{$ipAddress}");
                
                if ($response->successful() && $response->json('status') === 'success') {
                    return [
                        'latitude' => $response->json('lat'),
                        'longitude' => $response->json('lon'),
                        'country' => $response->json('country'),
                        'city' => $response->json('city'),
                        'isp' => $response->json('isp'),
                    ];
                }
            } catch (\Exception $e) {
                // Log error but don't fail the request
                \Log::warning("IP geolocation failed for {$ipAddress}: " . $e->getMessage());
            }

            return null;
        });
    }

    /**
     * Calculate distance between two coordinates (Haversine formula).
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check for impossible velocity between location updates.
     */
    private function checkImpossibleVelocity(int $userId, float $latitude, float $longitude): ?int
    {
        $lastDetection = GeoSpoofDetection::where('user_id', $userId)
            ->orderBy('detected_at', 'desc')
            ->first();

        if (!$lastDetection) {
            return null;
        }
        // Use seconds for higher precision and avoid truncation issues
        $diffSeconds = now()->diffInSeconds($lastDetection->detected_at);

        // Skip if less than 5 minutes between detections to reduce noise
        if ($diffSeconds < 300) {
            return null;
        }

        $hours = $diffSeconds / 3600.0;
        if ($hours <= 0) {
            return null;
        }

        $distanceKm = $this->calculateDistance(
            $lastDetection->latitude,
            $lastDetection->longitude,
            $latitude,
            $longitude
        );

        // Round to nearest whole km/h for consistency
        return (int) round($distanceKm / $hours);
    }

    /**
     * Check if IP is a known VPN or proxy.
     */
    private function isVpnOrProxy(string $ipAddress): bool
    {
        // Placeholder: In production, use a VPN detection service
        // Examples: IPQualityScore, ProxyCheck.io, IPHub
        return false;
    }

    /**
     * Check if IP belongs to a data center.
     */
    private function isDataCenterIp(string $ipAddress): bool
    {
        // Placeholder: Check against known data center IP ranges
        // Can use ASN databases or services like IPinfo
        return false;
    }

    /**
     * Check if user has frequent location changes (pattern analysis).
     */
    private function hasFrequentLocationChanges(int $userId): bool
    {
        $recentDetections = GeoSpoofDetection::where('user_id', $userId)
            ->where('detected_at', '>', now()->subDays(7))
            ->count();

        return $recentDetections > 10;
    }

    /**
     * Check if IP is private/localhost.
     */
    private function isPrivateIp(string $ipAddress): bool
    {
        return in_array($ipAddress, ['127.0.0.1', '::1', 'localhost']) ||
               str_starts_with($ipAddress, '192.168.') ||
               str_starts_with($ipAddress, '10.') ||
               str_starts_with($ipAddress, '172.16.') ||
               str_starts_with($ipAddress, '172.17.') ||
               str_starts_with($ipAddress, '172.18.');
    }

    /**
     * Get spoof detection statistics for a user.
     */
    public function getUserSpoofStats(int $userId): array
    {
        $total = GeoSpoofDetection::where('user_id', $userId)->count();
        $highRisk = GeoSpoofDetection::where('user_id', $userId)->highRisk()->count();
        $confirmed = GeoSpoofDetection::where('user_id', $userId)->where('is_confirmed_spoof', true)->count();

        return [
            'total_detections' => $total,
            'high_risk_detections' => $highRisk,
            'confirmed_spoofs' => $confirmed,
            'is_high_risk_user' => $highRisk > 0,
        ];
    }
}
