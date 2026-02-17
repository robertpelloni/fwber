<?php

namespace App\Services;

class LocationService
{
    /**
     * Generate geohash for location
     */
    public function generateGeohash(float $latitude, float $longitude, int $precision = 8): string
    {
        $latRange = [-90, 90];
        $lonRange = [-180, 180];
        
        $geohash = '';
        $isEven = true;
        $bit = 0;
        $ch = 0;
        
        while (strlen($geohash) < $precision) {
            if ($isEven) {
                $mid = ($lonRange[0] + $lonRange[1]) / 2;
                if ($longitude >= $mid) {
                    $ch |= (1 << (4 - $bit));
                    $lonRange[0] = $mid;
                } else {
                    $lonRange[1] = $mid;
                }
            } else {
                $mid = ($latRange[0] + $latRange[1]) / 2;
                if ($latitude >= $mid) {
                    $ch |= (1 << (4 - $bit));
                    $latRange[0] = $mid;
                } else {
                    $latRange[1] = $mid;
                }
            }
            
            $isEven = !$isEven;
            
            if ($bit < 4) {
                $bit++;
            } else {
                $geohash .= $this->base32Encode($ch);
                $bit = 0;
                $ch = 0;
            }
        }
        
        return $geohash;
    }

    /**
     * Base32 encoding for geohash
     */
    private function base32Encode(int $value): string
    {
        $base32 = '0123456789bcdefghjkmnpqrstuvwxyz';
        return $base32[$value];
    }

    /**
     * Calculate distance between two points in meters
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $earthRadius = 6371000; // Earth's radius in meters
        
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);
        
        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;
        
        $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlon/2) * sin($dlon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return round($earthRadius * $c);
    }
}
