# Event Schema v0 (2025-11-08)

Canonical definitions for initial telemetry events. Enforced by `TelemetryService` validation rules.

| Event | Purpose | Fields | Notes |
|-------|---------|--------|-------|
| user.signup | Track new user registrations | user_id:int, method:string | method: email,oauth,invite |
| user.profile.completed | Measure onboarding completion | user_id:int, completion_rate:float(0-1) | completion_rate derived from filled sections |
| feed.viewed | Record feed fetch & count | user_id:int, count:int | count = items returned |
| message.sent | Message send attempt | from_user_id:int, to_user_id:int, message_id?:string | message_id generated post-persist |
| message.received | Delivery confirmation | from_user_id:int, to_user_id:int, message_id?:string | Emitted when receiver client ack processed |
| moderation.flagged | AI or rule flagged content | user_id:int, category:string, confidence:float | category e.g. harassment |
| moderation.actioned | Final moderation decision | user_id:int, action:string(in:reject,review,flag,approve) | Action after aggregation |

## Versioning
- v0: Flat events, minimal required fields
- v1 (planned): Add request_id correlation, latency metrics fields

## Validation Source
Implemented in `config/telemetry.php` and enforced by `TelemetryService::emit()`.

## Instrumentation Order
1. user.signup
2. user.profile.completed
3. feed.viewed
4. message.sent/message.received
5. moderation.flagged/moderation.actioned

## Dashboard Minimums
- Daily user.signup count
- Profile completion funnel (signup vs profile.completed)
- Feed engagement: feed.viewed counts per DAU
- Messaging reliability: sent vs received ratio
- Moderation volumes: flagged vs actioned distribution

## Data Integrity Checks
Daily job: ensure message.received >= 0.95 * message.sent (threshold adjust later).
