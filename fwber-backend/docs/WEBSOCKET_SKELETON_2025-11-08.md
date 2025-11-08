# WebSocket Skeleton (2025-11-08)

This document outlines the initial skeleton for WebSocket messaging using the existing `WebSocketService`.

## Goals
- Authenticated WS connections (token-based)
- Basic channels: user, notifications, presence
- Heartbeat + connection limits via Redis

## Existing Pieces
- `App\Services\WebSocketService` – connection handling, presence, send APIs
- Redis pub/sub channels configurable via `config/websocket.php` (fallbacks in service)

## Next Steps
1. Expose HTTP endpoint to mint short-lived WS tokens (signed JWT or random opaque with TTL)
2. Implement WS gateway (Node/PHP Swoole/Go) that:
   - Subscribes to Redis channel `websocket`
   - Bridges messages to connected clients
   - Validates token on connect; sets `connection_users` and `user_connections:*`
3. Add heartbeat (every 30s) and idle timeout (300s) enforcement
4. Add `message.send` REST endpoint that uses `WebSocketService::handleChatMessage`
5. Add soak test script (6h) to measure p95 delivery time

## Rollout Plan
- Dark launch (internal only) → 5% → 25% → 100%
- Gate via `FLAG_MESSAGING_WS`
