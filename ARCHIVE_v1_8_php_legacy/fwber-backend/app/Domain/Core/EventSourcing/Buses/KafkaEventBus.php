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
        $payload = json_encode([
            'event_id' => $event->eventId,
            'aggregate_uuid' => $event->aggregateUuid,
            'aggregate_type' => $aggregateType,
            'event_type' => get_class($event),
            'data' => $event->payload(),
            'metadata' => $metadata,
            'occurred_at' => $event->occurredAt->format('Y-m-d H:i:s.u'),
        ]);

        if (!$this->producer) {
            // Simulated Kafka: Write to partition files
            $partition = crc32($event->aggregateUuid) % 10;
            $path = storage_path("app/events/partition_{$partition}.log");
            @mkdir(dirname($path), 0755, true);
            file_put_contents($path, $payload . PHP_EOL, FILE_APPEND);
            
            Log::debug("Kafka [Simulated]: Event {$event->eventId} written to partition {$partition}");
            return;
        }

        $topic = $this->producer->newTopic(config('events.drivers.kafka.topic'));
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $payload, $event->aggregateUuid);
        $this->producer->flush(1000);
    }
}
