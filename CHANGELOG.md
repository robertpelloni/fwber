# Changelog

All notable changes to this project will be documented in this file.

## [1.0.25] - 2026-04-01 — Recommendations Feed Schema Repair

### Fixed
- Corrected recommendation engagement scoring to query `events.starts_at`, matching the live schema and preventing production `/api/recommendations/feed` failures caused by the nonexistent `start_time` column.
- Added regression coverage proving recommendation engagement scoring counts recent attended events through the `starts_at` event timestamp.
- Fixed the subscription settings page and swipe matches page to consume the shared frontend API client's unwrapped responses correctly instead of reading nonexistent nested `data` payloads.
## [1.0.27] - 2026-04-01 — Recommendation And Matching Resilience

### Fixed
- Made the recommendation service evaluate only the requested recommendation source types so `ai`, `location`, `mixed`, and feed requests stop triggering unrelated brittle code paths.
- Fixed location recommendations to query bulletin boards through the real `center_lat` and `center_lng` schema instead of the nonexistent `location` column.
- Hardened recommendation source execution so a failing source logs and degrades to an empty slice instead of returning a 500 for the whole recommendations response.
- Added an AI matching fallback guard so vector embedding/search failures fall back to heuristic matching rather than crashing `/api/matches`.
- Added regression coverage for location-only recommendations and vector-failure fallback behavior.

## [1.0.26] - 2026-04-01 — Frontend API Contract Repair

### Fixed
- Repaired the main subscription management page to consume the shared frontend `api` helper correctly for subscription lists, payment history, and cancel responses instead of dereferencing nonexistent nested `data` payloads.
- Realigned the friends page with the backend's actual `/friends`, `/friends/requests`, `/friends/search`, and `/friends/requests/{userId}` routes so loading, searching, sending requests, and responding to requests no longer call stale endpoints.
- Fixed the public pulse node page to consume the unwrapped public payload directly so live venue pulse screens render instead of failing on `response.data`.

## [1.0.24] - 2026-04-01 — Sanctum Token Touch Throttle

### Fixed
- Registered a custom Sanctum personal access token model that throttles `last_used_at` persistence to once per configured interval instead of writing on every bearer-authenticated request.
- Added backend regression coverage proving first-touch, within-window skip, and post-window refresh behavior for throttled token usage tracking.

## [1.0.23] - 2026-04-01 — Notification Polling Load Reduction

### Fixed
- Changed the header notification bell to poll `GET /notifications/count` while the drawer is closed instead of fetching the full notifications payload every 30 seconds.
- Kept the full `GET /notifications` refresh behavior for the open drawer so the UI still shows fresh notification details when the user is actively viewing them.
- Typed the dashboard leaderboard query explicitly so frontend type-check validation succeeds while rendering the vouch leaderboard widget.

## [1.0.22] - 2026-04-01 — Tagged Cache Runtime Fallback

### Fixed
- Hardened the shared `TaggedCache` helper so runtime `BadMethodCallException` tag failures fall back to the namespaced non-taggable cache strategy instead of crashing endpoints.
- Added unit coverage for both `remember()` and `flush()` fallback behavior and revalidated the `/api/matches` feature path with the match filter suites.

## [1.0.21] - 2026-04-01 — Health Version Source Repair

### Fixed
- Updated the backend app version config to read the repository `VERSION` file so `/api/health` reports the actual deployed release instead of the stale `1.0.2` fallback.
- Added regression coverage to ensure the health endpoint version matches the repository version signal used for deployment tracking.

## [1.0.20] - 2026-04-01 — Notification Schema Memoization

### Fixed
- Memoized legacy notification schema detection inside `NotificationController` so a single `/api/notifications` request no longer repeats the same cache-backed schema capability lookup.
- Added regression coverage proving the legacy-schema cache is resolved only once per request while preserving legacy notification compatibility behavior.

## [1.0.17] - 2026-04-01 — Auth Restore Network Fallback

### Fixed
- Reused cached auth state when `/auth/me` restoration fails with a browser fetch/network error instead of clearing a valid session.
- Consolidated auth-cache restoration into a shared helper so transient restore failures and critical init failures behave consistently.

## [1.0.18] - 2026-04-01 — Production Cache And Recommendations Repair

### Fixed
- Added a backend tagged-cache fallback so production endpoints keep working when the active cache store does not support Laravel cache tags.
- Repaired live `subscriptions` and `matches` endpoints to use the safe cache fallback instead of crashing with tag-support exceptions.
- Made recommendations endpoints accept serialized `types` and `context` query params, restored the missing route/controller compatibility methods, and returned the frontend's expected `recommendations`/`metadata` response shape.
- Fixed the federation settings page to consume the shared API client's unwrapped responses correctly and guard against non-array payloads.

## [1.0.19] - 2026-04-01 — Cache Fallback Expansion And Notification Schema Caching

### Fixed
- Expanded the non-taggable cache fallback across additional production runtime paths including events, profile views, Stripe subscription invalidation, subscription cleanup jobs, and AI matching cache invalidation.
- Cached legacy notification schema detection so repeated notification requests stop querying `information_schema` on every hot path hit.
- Preserved the existing `v1.0.18` production fixes while reducing the risk of future DreamHost cache-tag regressions in adjacent endpoints.

## [1.0.16] - 2026-04-01 — Auth Restore And Legacy Notifications Stability

### Fixed
- Prevented transient `/auth/me` failures and early protected requests from wiping valid frontend auth state during session restore.
- Delayed photo and notification fetches until auth initialization completes so boot-time `401` responses do not trigger logout loops.
- Completed legacy DreamHost notifications support for unread counts and mark-as-read flows, with regression coverage.

## [1.0.15] - 2026-04-01 — Notifications Legacy Schema Compatibility

### Fixed
- Made the backend notifications endpoint support both legacy `notifications(user_id,title,body,...)` tables and newer Laravel-style notification tables.
- Preserved malformed-payload hardening while avoiding production SQL errors on older DreamHost schema versions.
- Added regression coverage for the legacy notifications schema path.

## [1.0.14] - 2026-04-01 — Notifications Payload Hardening

### Fixed
- Hardened the backend notifications feed to decode stored payloads defensively and survive malformed notification rows instead of returning HTTP 500 for the whole endpoint.
- Normalized notification response data to JSON-safe UTF-8 strings before returning it to authenticated clients.
- Added a regression test covering malformed stored notification payloads.

## [1.0.13] - 2026-04-01 — Notifications Route Repair

### Fixed
- Removed the backend `/notifications` route conflict so authenticated notification fetches resolve to `NotificationController` instead of the device-token listing controller.
- Disabled prefetch on the login page's `/register` and `/test-auth` links to avoid background RSC fallback noise there.
- Suppressed non-actionable websocket connection console noise for known transient `WebSocketError`/auth-code cases.

## [1.0.12] - 2026-04-01 — Auth Token Source Hardening

### Fixed
- Added an in-memory auth token source in the shared API client so freshly authenticated sessions can make protected requests immediately, even before browser storage effects settle.
- Cleared the shared API client token alongside browser auth storage on logout and auth-expiry handling to keep token state consistent.

## [1.0.11] - 2026-04-01 — Auth Persistence Race Fix

### Fixed
- Persisted freshly issued auth tokens to browser storage immediately during login, wallet login, two-factor completion, and registration.
- Prevented immediate post-login authenticated requests from racing ahead of token persistence and triggering a false logout/session-expired redirect.

## [1.0.10] - 2026-04-01 — Final Route Prefetch Cleanup

### Fixed
- Disabled the last remaining `/messages` and `/proximity` `Link` prefetch paths outside the main app shell, including dashboard legacy cards and proximity presence widgets.
- Reduced the remaining protected-route RSC fallback noise to actual click-time navigation instead of background prefetch attempts.

## [1.0.9] - 2026-04-01 — Asset Recovery & Protected Prefetch Hardening

### Fixed
- Disabled app-shell prefetch for authenticated header and sidebar routes beyond the original small subset, covering the remaining noisy navigation targets such as messages, groups, events, conference pulse, and proximity chatrooms.
- Stopped dashboard achievements from logging expected unauthenticated errors after auth expiry or stale session state.
- Allowed stale asset recovery to retry after a short cooldown instead of blocking all further recovery attempts for the same version.

## [1.0.7] - 2026-04-01 — Dashboard Auth Query Cleanup

### Fixed
- Replaced remaining dashboard widget calls that still bypassed the shared authenticated API client for stats, leaderboard, activity, profile completeness, and boosts.
- Gated achievements and related protected dashboard widgets so they do not fire unauthenticated requests during auth initialization.
- Reduced stray direct browser requests to `api.fwber.me` that were still producing unauthenticated and network-noise logs after the earlier production fixes.

## [1.0.8] - 2026-04-01 — Protected Route Navigation Noise Reduction

### Fixed
- Disabled client prefetch on the remaining protected dashboard and settings navigation links that were surfacing noisy RSC payload fallback logs during background prefetch.
- Stopped `SafeWalkTracker` from logging expected auth errors after session expiry or logout by treating authenticated API failures as a quiet reset instead of a console error.

## [1.0.6] - 2026-04-01 — Payments, Notifications & Realtime Hardening

### Fixed
- Guarded Stripe initialization so card-payment UI fails closed instead of throwing when `NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY` is missing.
- Removed the stray `NEXT_PUBLIC_STRIPE_KEY` usage and aligned wallet/premium payment flows on the publishable-key setting.
- Routed notification UI requests through the shared API client and added `GET /api/notifications/count`.
- Hardened backend notification serialization so malformed legacy notification payloads do not 500 the entire notifications feed.
- Prevented realtime from guessing invalid production websocket hosts when Reverb/Pusher env is incomplete, reducing noisy connection errors.

## [1.0.5] - 2026-04-01 — Frontend Auth & Asset Recovery

### Fixed
- Revalidated restored frontend sessions against `GET /api/auth/me` before marking users authenticated, preventing false localStorage-only login state and repeated `401 Unauthenticated` cascades.
- Added client-side stale asset recovery that unregisters service workers, clears caches, and reloads once when old `_next` chunk or CSS references break after deployment.
- Forced browser-side auth flows in `AuthProvider` to use the Next.js `/api` proxy instead of drifting to direct backend URLs.
