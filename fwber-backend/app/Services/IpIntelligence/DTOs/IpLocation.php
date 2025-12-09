<?php

namespace App\Services\IpIntelligence\DTOs;

class IpLocation
{
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly ?string $country = null,
        public readonly ?string $city = null,
        public readonly ?string $isp = null,
        public readonly bool $isVpn = false,
        public readonly bool $isDataCenter = false
    ) {}

    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'country' => $this->country,
            'city' => $this->city,
            'isp' => $this->isp,
            'is_vpn' => $this->isVpn,
            'is_datacenter' => $this->isDataCenter,
        ];
    }
}
