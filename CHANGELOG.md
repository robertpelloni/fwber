# Changelog

All notable changes to this project will be documented in this file.

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
