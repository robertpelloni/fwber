# Cache Strategy & Invalidation Guide

## Overview
fwber uses Redis with tag-based caching to provide high-performance data retrieval while maintaining data consistency. This document outlines the caching architecture, key structure, and invalidation strategies.

## Configuration
- **Driver**: Redis
- **Versioning**: Controlled by `CACHE_VERSION` in `.env` (default: `v1`).
- **Config**: `config/optimization.php`

## Cache Key Structure
All cache keys are prefixed with the `CACHE_VERSION` to allow for global invalidation during major updates.

Format: `{version}:{context}:{identifier}:{params_hash}`

Examples:
- `v1:groups:index:public`
- `v1:events:index:a1b2c3d4...` (hash of query params)
- `v1:subscriptions:user:123`

## Tagging Strategy
We use Redis tags to group related cache entries for efficient bulk invalidation.

| Tag | Scope | Usage | Invalidation Trigger |
|-----|-------|-------|----------------------|
| `groups` | Global | Public group listings | New group created, member join/leave |
| `events` | Global | Event listings, search results | New event, RSVP change |
| `subscriptions` | Global | Subscription data | Webhook events (created, updated, deleted) |
| `user:{id}` | User-specific | User's subscriptions, payment history | Specific user updates |

## Invalidation Logic

### Groups
- **Trigger**: `GroupController@store`, `join`, `leave`
- **Action**: `Cache::tags(['groups'])->flush()`
- **Effect**: Clears all cached group listings.

### Events
- **Trigger**: `EventController@store`, `rsvp`
- **Action**: `Cache::tags(['events'])->flush()`
- **Effect**: Clears all cached event search results.

### Subscriptions
- **Trigger**: `StripeWebhookController` (all events)
- **Action**: `Cache::tags(['subscriptions', "user:{$userId}"])->flush()`
- **Effect**: Clears only the specific user's subscription and payment history cache.

## Cache Warming
To prevent "cold start" latency for popular endpoints, a scheduled task warms the cache every 5 minutes.

- **Command**: `php artisan cache:warm`
- **Schedule**: Every 5 minutes
- **Targets**:
  - Public Groups Listing (`groups:index:public`)
  - Default Events Listing (Page 1, no filters)

## Monitoring
Use the custom Artisan command to view Redis metrics:
```bash
php artisan cache:stats
```
Metrics to watch:
- **Hit Ratio**: Should be > 80% for a healthy system.
- **Used Memory**: Ensure it doesn't exceed Redis limits.
- **Keyspace Misses**: High misses might indicate ineffective caching or aggressive invalidation.

## Versioning
To invalidate **ALL** application cache (e.g., after a major deployment with schema changes):
1. Update `CACHE_VERSION` in `.env` (e.g., `v1` -> `v2`).
2. Deploy.
3. Old keys will naturally expire or can be flushed manually.
