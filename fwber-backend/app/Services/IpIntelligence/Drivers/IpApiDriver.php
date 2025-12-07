<?php

namespace App\Services\IpIntelligence\Drivers;

use App\Services\IpIntelligence\IpIntelligenceInterface;
use App\Services\IpIntelligence\DTOs\IpLocation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpApiDriver implements IpIntelligenceInterface
{
    public function analyze(string $ipAddress): ?IpLocation
    {
        if ($this->isPrivateIp($ipAddress)) {
            return null;
        }

        try {
            // Using ip-api.com (free tier)
            $response = Http::timeout(5)->get("http://ip-api.com/json/{$ipAddress}?fields=status,message,country,city,lat,lon,isp,proxy,hosting");
            
            if ($response->successful() && $response->json('status') === 'success') {
                $data = $response->json();
                
                return new IpLocation(
                    latitude: (float) $data['lat'],
                    longitude: (float) $data['lon'],
                    country: $data['country'] ?? null,
                    city: $data['city'] ?? null,
                    isp: $data['isp'] ?? null,
                    // ip-api 'proxy' field indicates VPN/Proxy
                    isVpn: $data['proxy'] ?? false,
                    // ip-api 'hosting' field indicates Data Center
                    isDataCenter: $data['hosting'] ?? false
                );
            }
        } catch (\Exception $e) {
            Log::warning("IP geolocation failed for {$ipAddress}: " . $e->getMessage());
        }

        return null;
    }

    private function isPrivateIp(string $ipAddress): bool
    {
        return in_array($ipAddress, ['127.0.0.1', '::1', 'localhost']) ||
               str_starts_with($ipAddress, '192.168.') ||
               str_starts_with($ipAddress, '10.') ||
               str_starts_with($ipAddress, '172.16.') ||
               str_starts_with($ipAddress, '172.17.') ||
               str_starts_with($ipAddress, '172.18.');
    }
}
