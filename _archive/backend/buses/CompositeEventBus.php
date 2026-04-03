<?php

namespace App\Domain\Core\EventSourcing\Buses;

use App\Domain\Core\Events\DomainEvent;
use App\Domain\Core\EventSourcing\Contracts\EventBusInterface;

class CompositeEventBus implements EventBusInterface
{
    /** @var EventBusInterface[] */
    protected array $buses;

    public function __construct(array $buses)
    {
        $this->buses = $buses;
    }

    public function publish(DomainEvent $event, string $aggregateType, array $metadata = []): void
    {
        foreach ($this->buses as $bus) {
            $bus->publish($event, $aggregateType, $metadata);
        }
    }
}
