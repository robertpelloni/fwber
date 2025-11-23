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
| face_blur_applied | Successful client blur prior to upload | user_id:int, photo_filename:string, original_filename:string, faces_detected:int, processing_ms?:int, client_backend:string, warning?:string, preview_id?:string | `preview_id` ties the upload to preview-stage events; client_backend currently `client`; warning populated when degraded blur runs |
| face_blur_skipped_reason | Blur skipped or failed client-side | user_id:int, photo_filename:string, original_filename:string, reason:string, faces_detected?:int, warning?:string, preview_id?:string | `preview_id` aligns skips with preview telemetry (values include `no_faces_detected`, `model_load_failed`, `processing_failed`, `unsupported_env`) |
| face_blur_preview_ready | Preview generated before upload | user_id:int, preview_id:string, file_name:string, faces_detected?:int, blur_applied:bool, processing_ms?:int, backend:string(in:client,server), warning?:string, skipped_reason?:string | Fired once `blurFacesOnFile` resolves so we can measure latency + success before upload |
| face_blur_preview_toggled | User switched preview view | user_id:int, preview_id:string, view:string(in:original,processed), faces_detected?:int, blur_applied?:bool, warning?:string | Helps gauge trust in blur UI (how often users inspect originals) |
| face_blur_preview_discarded | Preview removed pre-upload | user_id:int, preview_id:string, faces_detected?:int, blur_applied?:bool, discard_reason:string(in:user_removed,validation_failed), warning?:string | Surfaces opt-outs that never become uploads |

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
6. face_blur_preview_* (client) → face_blur_applied/face_blur_skipped_reason (server)

## Dashboard Minimums
- Daily user.signup count
- Profile completion funnel (signup vs profile.completed)
- Feed engagement: feed.viewed counts per DAU
- Messaging reliability: sent vs received ratio
- Moderation volumes: flagged vs actioned distribution

## Data Integrity Checks
Daily job: ensure message.received >= 0.95 * message.sent (threshold adjust later).

## Client Ingestion Endpoint
- Route: `POST /api/telemetry/client-events` (requires `auth.api`)
- Accepts batches up to 50 events with `{ name, payload, ts? }`
- Currently whitelisted events: `face_blur_preview_ready`, `face_blur_preview_toggled`, `face_blur_preview_discarded`
