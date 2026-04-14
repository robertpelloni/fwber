<?php

namespace App\Domain\Core\EventSourcing\Buses;

use App\Domain\Core\Events\DomainEvent;
use App\Domain\Core\EventSourcing\Contracts\EventBusInterface;
use Illuminate\Support\Facades\Redis;

class RedisStreamEventBus implements EventBusInterface
{
    /**
     * Publish using Redis Streams (XADD)
     */
    public function publish(DomainEvent $event, string $aggregateType, array $metadata = []): void
    {
        $streamKey = "fwber:events:{$aggregateType}";
        
        $payload = [
            'event_id' => $event->eventId,
            'aggregate_uuid' => $event->aggregateUuid,
            'aggregate_type' => $aggregateType,
            'event_type' => get_class($event),
            'data' => json_encode($event->payload()),
            'metadata' => json_encode($metadata),
            'occurred_at' => $event->occurredAt->format('Y-m-d H:i:s.u'),
        ];

        // XADD stream_key * field value ...
        Redis::xadd($streamKey, '*', $payload);
        
        // Optional: Cap the stream length to 100k events to prevent OOM
        Redis::xtrim($streamKey, 100000, true);
    }
}
