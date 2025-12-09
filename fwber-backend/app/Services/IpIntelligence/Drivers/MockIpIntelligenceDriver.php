<?php

namespace App\Services\IpIntelligence\Drivers;

use App\Services\IpIntelligence\IpIntelligenceInterface;
use App\Services\IpIntelligence\DTOs\IpLocation;

class MockIpIntelligenceDriver implements IpIntelligenceInterface
{
    public function analyze(string $ipAddress): ?IpLocation
    {
        // Simulate a standard US location
        return new IpLocation(
            latitude: 37.7749,
            longitude: -122.4194,
            country: 'United States',
            city: 'San Francisco',
            isp: 'Mock ISP',
            isVpn: false,
            isDataCenter: false
        );
    }
}
