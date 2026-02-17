<?php

namespace App\Services\IpIntelligence\Drivers;

use App\Services\IpIntelligence\IpIntelligenceInterface;
use App\Services\IpIntelligence\DTOs\IpLocation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpInfoDriver implements IpIntelligenceInterface
{
    protected string $token;

    public function __construct()
    {
        $this->token = config('services.ipinfo.token', '');
    }

    public function analyze(string $ipAddress): ?IpLocation
    {
        try {
            $response = Http::get("https://ipinfo.io/{$ipAddress}?token={$this->token}");

            if ($response->failed()) {
                Log::warning("IpInfo API failed for IP {$ipAddress}: " . $response->body());
                return null;
            }

            $data = $response->json();

            // Parse coordinates "37.7749,-122.4194"
            $loc = explode(',', $data['loc'] ?? '');
            $lat = isset($loc[0]) ? (float) $loc[0] : null;
            $lon = isset($loc[1]) ? (float) $loc[1] : null;

            // Check for VPN/Proxy data if available (requires higher tier IPInfo plan)
            $privacy = $data['privacy'] ?? [];
            $isVpn = $privacy['vpn'] ?? false;
            $isProxy = $privacy['proxy'] ?? false;
            $isTor = $privacy['tor'] ?? false;
            $isHosting = $privacy['hosting'] ?? false; // Data center

            return new IpLocation(
                latitude: $lat,
                longitude: $lon,
                country: $data['country'] ?? null,
                city: $data['city'] ?? null,
                isp: $data['org'] ?? null, // IPInfo returns ISP/Org in 'org' field
                isVpn: $isVpn || $isProxy || $isTor,
                isDataCenter: $isHosting
            );

        } catch (\Exception $e) {
            Log::error("IpInfo Driver Error: " . $e->getMessage());
            return null;
        }
    }
}
