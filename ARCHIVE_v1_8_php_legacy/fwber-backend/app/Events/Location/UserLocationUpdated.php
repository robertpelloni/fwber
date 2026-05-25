<?php

namespace App\Events\Location;

use App\Domain\Core\Events\DomainEvent;

class UserLocationUpdated extends DomainEvent
{
    public function __construct(
        string $aggregateUuid,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly ?string $locationName = null
    ) {
        parent::__construct($aggregateUuid);
    }

    public function payload(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'location_name' => $this->locationName,
        ];
    }
}
