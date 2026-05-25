<?php

namespace App\Events\Messaging;

use App\Domain\Core\Events\DomainEvent;

class MessageSent extends DomainEvent
{
    public function __construct(
        string $aggregateUuid, // The message UUID (once we move to UUIDs) or Chatroom UUID
        public readonly int $senderId,
        public readonly int $receiverId,
        public readonly string $encryptedContent,
        public readonly string $type = 'text',
        public readonly ?string $metadata = null
    ) {
        parent::__construct($aggregateUuid);
    }

    public function payload(): array
    {
        return [
            'sender_id' => $this->senderId,
            'receiver_id' => $this->receiverId,
            'encrypted_content' => '[ENCRYPTED]', // We don't log raw encrypted content in the event store for extra safety, or we log the hash
            'content_hash' => hash('sha256', $this->encryptedContent),
            'type' => $this->type,
            'metadata' => $this->metadata,
        ];
    }
}
