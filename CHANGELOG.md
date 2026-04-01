# Changelog

All notable changes to this project will be documented in this file.

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
