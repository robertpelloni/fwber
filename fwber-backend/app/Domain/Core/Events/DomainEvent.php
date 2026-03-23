<?php

namespace App\Domain\Core\Events;

use Illuminate\Support\Str;

abstract class DomainEvent
{
    public readonly string $eventId;
    public readonly string $aggregateUuid;
    public readonly \DateTimeImmutable $occurredAt;

    public function __construct(string $aggregateUuid)
    {
        $this->eventId = Str::uuid()->toString();
        $this->aggregateUuid = $aggregateUuid;
        $this->occurredAt = new \DateTimeImmutable();
    }

    /**
     * Get the payload of the event for serialization.
     */
    abstract public function payload(): array;
}
