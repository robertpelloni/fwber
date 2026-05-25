<?php

namespace App\Domain\Core\EventSourcing\Contracts;

use App\Domain\Core\Events\DomainEvent;

interface EventBusInterface
{
    /**
     * Dispatch a domain event to the distributed stream.
     *
     * @param DomainEvent $event The event to publish.
     * @param string $aggregateType The type of aggregate.
     * @param array $metadata Additional context (IP, headers, etc).
     */
    public function publish(DomainEvent $event, string $aggregateType, array $metadata = []): void;
}
