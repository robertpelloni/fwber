<?php

namespace App\Domain\Core\EventSourcing\Buses;

use App\Domain\Core\Events\DomainEvent;
use App\Domain\Core\EventSourcing\Contracts\EventBusInterface;
use Illuminate\Support\Facades\Log;

/**
 * Production Kafka Event Bus
 * Requires 'rdkafka' PHP extension.
 */
class KafkaEventBus implements EventBusInterface
{
    protected $producer;

    public function __construct()
    {
        if (extension_loaded('rdkafka')) {
            $conf = new \RdKafka\Conf();
            $conf->set('metadata.broker.list', config('events.drivers.kafka.brokers'));
            $this->producer = new \RdKafka\Producer($conf);
        }
    }

    public function publish(DomainEvent $event, string $aggregateType, array $metadata = []): void
    {
        if (!$this->producer) {
            Log::warning("Kafka: Extension not loaded, falling back to Log.");
            (new LogEventBus())->publish($event, $aggregateType, $metadata);
            return;
        }

        $topic = $this->producer->newTopic(config('events.drivers.kafka.topic'));
        
        $payload = json_encode([
            'event_id' => $event->eventId,
            'aggregate_uuid' => $event->aggregateUuid,
            'aggregate_type' => $aggregateType,
            'event_type' => get_class($event),
            'data' => $event->payload(),
            'metadata' => $metadata,
            'occurred_at' => $event->occurredAt->format('Y-m-d H:i:s.u'),
        ]);

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $payload, $event->aggregateUuid);
        $this->producer->flush(1000);
    }
}
