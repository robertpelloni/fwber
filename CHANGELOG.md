# Changelog

All notable changes to this project will be documented in this file.

## [1.0.5] - 2026-04-01 — Frontend Auth & Asset Recovery

### Fixed
- Revalidated restored frontend sessions against `GET /api/auth/me` before marking users authenticated, preventing false localStorage-only login state and repeated `401 Unauthenticated` cascades.
- Added client-side stale asset recovery that unregisters service workers, clears caches, and reloads once when old `_next` chunk or CSS references break after deployment.
- Forced browser-side auth flows in `AuthProvider` to use the Next.js `/api` proxy instead of drifting to direct backend URLs.
