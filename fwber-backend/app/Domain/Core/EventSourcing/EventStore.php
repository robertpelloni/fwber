<?php

namespace App\Domain\Core\EventSourcing;

use App\Domain\Core\Events\DomainEvent;
use Illuminate\Support\Facades\DB;

class EventStore
{
    /**
     * Append a new event to the event store.
     *
     * @param  DomainEvent  $event  The event to append.
     * @param  string  $aggregateType  The type of the aggregate (e.g., 'UserLocation')
     * @param  int  $expectedVersion  The expected *next* version number (for optimistic concurrency)
     * @param  array  $metadata  Optional metadata (e.g., IP address)
     *
     * @throws \Exception If a concurrency conflict occurs.
     */
    public function append(DomainEvent $event, string $aggregateType, int $expectedVersion, array $metadata = []): void
    {
        DB::table('domain_events')->insert([
            'aggregate_uuid' => $event->aggregateUuid,
            'aggregate_type' => $aggregateType,
            'version' => $expectedVersion,
            'event_type' => get_class($event),
            'payload' => json_encode($event->payload()),
            'metadata' => json_encode($metadata),
            'recorded_at' => $event->occurredAt->format('Y-m-d H:i:s.u'),
        ]);

        // In a full CQRS setup, we would dispatch this to an Event Bus here
        // so that projectors can update the read models.
        event($event);
    }

    /**
     * Get the current highest version for an aggregate.
     */
    public function getCurrentVersion(string $aggregateUuid, string $aggregateType): int
    {
        return DB::table('domain_events')
            ->where('aggregate_uuid', $aggregateUuid)
            ->where('aggregate_type', $aggregateType)
            ->max('version') ?? 0;
    }
}
