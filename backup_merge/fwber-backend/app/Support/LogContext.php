<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Provides structured context for logging with request IDs, user context, and correlation tracking.
 * 
 * Usage:
 *   Log::info('User action', LogContext::make());
 *   Log::error('Payment failed', LogContext::make(['payment_id' => $id]));
 *   Log::warning('Webhook retry', LogContext::fromWebhook($webhookId, $eventType));
 */
class LogContext
{
    /**
     * Generate structured context array for logging.
     *
     * @param array $extra Additional context to merge
     * @return array
     */
    public static function make(array $extra = []): array
    {
        $context = [
            'request_id' => self::getRequestId(),
            'timestamp' => now()->toIso8601String(),
        ];

        // Add user context if authenticated
        if ($user = auth()->user()) {
            $context['user_id'] = $user->id;
            $context['user_tier'] = $user->tier ?? 'free';
        }

        // Add correlation ID if present (for distributed tracing)
        if ($correlationId = request()->header('X-Correlation-ID')) {
            $context['correlation_id'] = $correlationId;
        }

        // Add request metadata
        if (app()->runningInConsole()) {
            $context['context'] = 'console';
        } else {
            $context['ip'] = request()->ip();
            $context['url'] = request()->fullUrl();
            $context['method'] = request()->method();
            $context['user_agent'] = request()->userAgent();
        }

        return array_merge($context, $extra);
    }

    /**
     * Create context for WebSocket operations.
     *
     * @param string|null $connectionId WebSocket connection ID
     * @param string|null $eventType WebSocket event type
     * @param array $extra Additional context
     * @return array
     */
    public static function fromWebSocket(?string $connectionId = null, ?string $eventType = null, array $extra = []): array
    {
        return self::make(array_merge([
            'channel' => 'websocket',
            'connection_id' => $connectionId,
            'event_type' => $eventType,
        ], $extra));
    }

    /**
     * Create context for webhook operations.
     *
     * @param string $webhookId External webhook ID (e.g., Stripe event ID)
     * @param string $eventType Webhook event type
     * @param array $extra Additional context
     * @return array
     */
    public static function fromWebhook(string $webhookId, string $eventType, array $extra = []): array
    {
        return self::make(array_merge([
            'channel' => 'webhook',
            'webhook_id' => $webhookId,
            'event_type' => $eventType,
        ], $extra));
    }

    /**
     * Create context for payment operations.
     *
     * @param string|null $transactionId Payment gateway transaction ID
     * @param string|null $gateway Payment gateway name
     * @param array $extra Additional context
     * @return array
     */
    public static function fromPayment(?string $transactionId = null, ?string $gateway = null, array $extra = []): array
    {
        return self::make(array_merge([
            'channel' => 'payment',
            'transaction_id' => $transactionId,
            'gateway' => $gateway,
        ], $extra));
    }

    /**
     * Get or generate request ID for distributed tracing.
     *
     * @return string
     */
    protected static function getRequestId(): string
    {
        // Check if request ID already set in request lifecycle
        if ($requestId = request()->attributes->get('request_id')) {
            return $requestId;
        }

        // Check for forwarded request ID from load balancer/proxy
        if ($requestId = request()->header('X-Request-ID')) {
            request()->attributes->set('request_id', $requestId);
            return $requestId;
        }

        // Generate new request ID
        $requestId = Str::uuid()->toString();
        request()->attributes->set('request_id', $requestId);
        
        return $requestId;
    }
}
