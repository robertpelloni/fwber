<?php

namespace App\Services;

interface AutoReplyServiceInterface
{
    /**
     * Determine if an auto-reply should be sent.
     *
     * @param  int  $userId  The user receiving the message.
     * @param  string  $incomingMessage  The message content.
     */
    public function shouldReply(int $userId, string $incomingMessage): bool;

    /**
     * Generate the reply content.
     */
    public function generateReply(int $userId): string;

    /**
     * Send the reply.
     *
     * @param  int  $senderId  The user sending the auto-reply (the offline user).
     * @param  int  $recipientId  The user receiving the reply.
     */
    public function sendReply(int $senderId, int $recipientId, string $content): void;
}
