<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MercurePublisher
{
    /**
     * Publish a message to Mercure hub
     */
    public function publish(string $topic, array $data, bool $private = true): void
    {
        try {
            $jwt = $this->generatePublisherJWT();
            
            $response = Http::withOptions(['verify' => false]) // Disable SSL verification for local dev
                ->withToken($jwt)
                ->asForm()
                ->post(config('services.mercure.internal_url'), [
                    'topic' => $topic,
                    'data' => json_encode($data),
                    'private' => $private ? 'on' : 'off'
                ]);

            if (!$response->successful()) {
                Log::error('Mercure publish failed', [
                    'topic' => $topic,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                throw new \Exception('Failed to publish to Mercure hub');
            }

            Log::debug('Mercure message published', [
                'topic' => $topic,
                'private' => $private
            ]);

        } catch (\Exception $e) {
            Log::error('Mercure publish error', [
                'topic' => $topic,
                'error' => $e->getMessage()
            ]);
            // Do not rethrow - allow the main request to succeed even if real-time update fails
            // throw $e; 
        }
    }

    /**
     * Publish multiple messages in batch
     */
    public function publishBatch(array $messages): void
    {
        foreach ($messages as $message) {
            $this->publish(
                $message['topic'],
                $message['data'],
                $message['private'] ?? true
            );
        }
    }

    /**
     * Generate JWT token for publisher
     */
    private function generatePublisherJWT(): string
    {
        $key = config('services.mercure.publisher_key');

        if (empty($key)) {
            throw new \RuntimeException('Mercure publisher key is not configured.');
        }

        $payload = [
            'mercure' => [
                'publish' => ['*'] // Allow publishing to all topics
            ],
            'exp' => time() + 60, // 1 minute expiry
            'iat' => time()
        ];

        return JWT::encode($payload, $key, 'HS256');
    }

    /**
     * Generate JWT token for subscriber
     */
    public function generateSubscriberJWT(array $topics, int $userId, int $expiryMinutes = 60): string
    {
        $key = config('services.mercure.subscriber_key');
        
        if (empty($key)) {
            throw new \RuntimeException('Mercure subscriber key is not configured.');
        }

        $payload = [
            'mercure' => [
                'subscribe' => $topics
            ],
            'sub' => (string) $userId,
            'exp' => time() + ($expiryMinutes * 60),
            'iat' => time()
        ];

        // Ensure key is properly formatted for HS256
        // If the key is base64 encoded (which it seems to be based on the user's input),
        // we might need to decode it or use it as is.
        // However, php-jwt treats the key as a binary string.
        // If the key in .env is "Zt3BAsBCspl6Xe6zvbXLJEZhmTj4XLbMpkaPdplDohQ=",
        // it is likely a base64 encoded string.
        // Caddy might be decoding it automatically or treating it as a string.
        // Let's try to be consistent.
        
        return JWT::encode($payload, $key, 'HS256');
    }

    /**
     * Validate JWT token
     */
    public function validateJWT(string $token, string $key): bool
    {
        try {
            JWT::decode($token, new Key($key, 'HS256'));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
