<?php

namespace App\Domain\Core\EventSourcing\Buses;

use App\Domain\Core\Events\DomainEvent;
use App\Domain\Core\EventSourcing\Contracts\EventBusInterface;
use App\Models\FederatedInstance;
use App\Services\ActivityPubService;
use Illuminate\Support\Facades\Log;

class FederatedRelayBus implements EventBusInterface
{
    /**
     * Relay the event to other fwber instances via ActivityPub.
     */
    public function publish(DomainEvent $event, string $aggregateType, array $metadata = []): void
    {
        $instances = FederatedInstance::where('status', 'active')
            ->where('relay_enabled', true)
            ->get();

        if ($instances->isEmpty()) return;

        $activity = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Sync',
            'actor' => url('/api/federation/instance-actor'),
            'object' => [
                'type' => 'DomainEvent',
                'event_type' => get_class($event),
                'aggregate_uuid' => $event->aggregateUuid,
                'aggregate_type' => $aggregateType,
                'payload' => $event->payload(),
                'occurred_at' => $event->occurredAt->format('Y-m-d H:i:s.u'),
            ]
        ];

        $apService = app(ActivityPubService::class);

        foreach ($instances as $instance) {
            // In a real system, we'd queue this to prevent blocking the request
            try {
                // We'll skip for now if no actor_uri is set
                if (!$instance->actor_uri) continue;
                
                // For this milestone, we log the intent
                Log::debug("Federation Relay: Syncing event " . get_class($event) . " to {$instance->domain}");
                
                // In production: $apService->dispatchToRemoteInbox($instanceActor, $instance->actor_uri, $activity);
            } catch (\Exception $e) {
                Log::error("Federation Relay: Failed for {$instance->domain}: " . $e->getMessage());
            }
        }
    }
}
