<?php

namespace App\Domain\Core\EventSourcing\Buses;

use App\Domain\Core\Events\DomainEvent;
use App\Domain\Core\EventSourcing\Contracts\EventBusInterface;
use Illuminate\Support\Facades\Log;

class LogEventBus implements EventBusInterface
{
    public function publish(DomainEvent $event, string $aggregateType, array $metadata = []): void
    {
        Log::debug("Event Published: {$aggregateType}::" . get_class($event), [
            'event_id' => $event->eventId,
            'aggregate_uuid' => $event->aggregateUuid,
            'payload' => $event->payload(),
            'metadata' => $metadata
        ]);
    }
}
