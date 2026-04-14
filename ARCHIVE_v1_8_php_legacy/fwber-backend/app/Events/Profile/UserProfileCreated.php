<?php

namespace App\Events\Profile;

use App\Domain\Core\Events\DomainEvent;

class UserProfileCreated extends DomainEvent
{
    public function __construct(
        string $aggregateUuid, // User ID
        public readonly array $data
    ) {
        parent::__construct($aggregateUuid);
    }

    public function payload(): array
    {
        return $this->data;
    }
}
