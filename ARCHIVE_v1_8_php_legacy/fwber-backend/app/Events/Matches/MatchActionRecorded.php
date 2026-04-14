<?php

namespace App\Events\Matches;

use App\Domain\Core\Events\DomainEvent;

class MatchActionRecorded extends DomainEvent
{
    public function __construct(
        string $aggregateUuid, // The user performing the action
        public readonly int $targetUserId,
        public readonly string $action, // like, pass, super_like
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null
    ) {
        parent::__construct($aggregateUuid);
    }

    public function payload(): array
    {
        return [
            'target_user_id' => $this->targetUserId,
            'action' => $this->action,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
