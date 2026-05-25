<?php

namespace App\Services\IpIntelligence;

use App\Services\IpIntelligence\DTOs\IpLocation;

interface IpIntelligenceInterface
{
    /**
     * Get geolocation and intelligence data for an IP address.
     */
    public function analyze(string $ipAddress): ?IpLocation;
}
