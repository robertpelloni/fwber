<?php

namespace App\Services\IpIntelligence;

use App\Services\IpIntelligence\DTOs\IpLocation;

interface IpIntelligenceInterface
{
    /**
     * Get geolocation and intelligence data for an IP address.
     *
     * @param string $ipAddress
     * @return IpLocation|null
     */
    public function analyze(string $ipAddress): ?IpLocation;
}
