# FWBer Roadmap Refresh — November 25, 2025

## Executive snapshot
- **Mission**: Privacy-first proximity dating with AI guidance, realtime chat, and strong moderation.
- **State**: Back-end feature set (analytics, rate limiting, moderation, AI content, proximity chatrooms) is implemented behind feature flags. Front-end coverage is uneven—several admin/ops views still mock data or call missing `/api/*` proxies, so operators cannot exercise the new APIs yet.
- **Guiding themes**: (1) Admin observability, (2) Safety & trust tooling, (3) Realtime cohesion, (4) Privacy-preserving media.

## Architecture highlights (2025-11-25)
| Layer | Key tech | Notes |
| --- | --- | --- |
| Backend | Laravel 12 API (`fwber-backend/`) | Feature flags in `config/features.php`; analytics + rate-limit controllers live but lightly surfaced. Mercure/WebSocket helpers available. |
| Frontend | Next.js 14 App Router (`fwber-frontend/`) | Auth context supplies bearer tokens; API utilities in `lib/api/client.ts`. Several pages (e.g., `/analytics`) still use `fetch('/api/...')` expecting Next API proxies that do not exist. |
| AI + Safety | Content generation + moderation services, AdvancedRateLimiting, Face Reveal, Local Media Vault spikes | Need frontend UI + telemetry to close the loop. |
| Tooling | Cypress, PHPUnit, MCP automation, docs/roadmap set | `PROJECT_STATUS_2025-11-24.md` is the authoritative status doc; AGENTS workflow requires incremental, flag-aware work. |

## Current gaps worth addressing
1. **Admin telemetry blind spots** – Analytics + rate-limit views exist in UI but never hit backend (relative `/api` paths 404). No hooks for realtime metrics, export, or empty states.
2. **Moderation dashboards** – Backend moderation endpoints (shadow throttling, review queues) arent exposed in frontend aside from basic block/report flows.
3. **Realtime cohesion** – WebSocketProvider is live, yet chatrooms/proximity boards still mix SSE polls and manual refresh. Need unified presence/typing components.
4. **Privacy surface follow-through** – Face Reveal + Local Media Vault backends are ready, but frontend gating, onboarding, and telemetry are pending.
5. **Operational readiness** – No consolidated ops console for feature flags, rate limits, analytics, and Mercure health; requires manual API use.

## Prioritized roadmap (next 6 weeks)
### 1. Admin observability bundle (Week 0-1)
- Wire `/analytics`, `/analytics/realtime`, `/analytics/moderation`, `/rate-limits/stats` into the Next.js admin dashboard using `lib/api/client` + React Query.
- Add CSV export + last-updated indicators.
- Surface feature flag states + Mercure connection health tile.
- **Success**: Admin view renders real backend data, refreshes live, and errors are actionable.

### 2. Safety & moderation cockpit (Week 1-3)
- Build `/moderation` page showing flagged artifacts, shadow-throttle queue, geo-spoof alerts (feature-flagged `feature:moderation`).
- Include bulk actions + audit trail download.
- Instrument analytics events for moderator actions.

### 3. Realtime cohesion (Week 2-4)
- Promote `WebSocketProvider` into chatrooms, matches, and proximity feeds (replace redundant polling, consolidate presence/typing indicators).
- Add connection diagnostics + fallbacks (Mercure -> SSE -> poll).
- Update Cypress tests (`realtime-chat.cy.js`) to cover multi-room flows.

### 4. Privacy-forward media flows (Week 3-5)
- Ship Face Reveal frontend (tier-based unlock UI + secure modal) and Local Media Vault beta onboarding behind respective `NEXT_PUBLIC_*` flags.
- Document risk disclaimers + telemetry for reveal events.

### 5. Ops & testing hardening (Week 4-6)
- Add admin Settings screen to toggle feature flags (via secured backend endpoint).
- Expand smoke tests to cover analytics/rate-limit endpoints + moderation flows.
- Automate Mercure + queue health probes in docs/DEPLOYMENT.

## Dependencies & sequencing notes
- Admin observability unblockers: ensure `NEXT_PUBLIC_API_URL` is set; leverage `lib/api/client.ts` for token injection. No backend changes required.
- Moderation cockpit depends on verifying `feature:moderation` gating and ensuring OA schemas documented in `Schemas.php` to avoid Swagger regressions.
- Realtime cohesion assumes WebSocket infrastructure from PROJECT_STATUS (already "complete"); focus on client adoption + fallback strategy.
- Privacy flows require coordinated messaging in `docs/FEATURE_FLAGS.md` + onboarding copy; coordinate with content team for disclaimers.

## Immediate action items (kicked off this session)
1. Replace `/api/*` placeholder calls on `/analytics` + `RateLimitStats` with typed admin API helpers that hit the Laravel backend.
2. Add reusable hooks for analytics + rate limit data (React Query) with skeleton states and retry/backoff.
3. Document this roadmap refresh (this file) so future agents align on priorities without re-auditing the repo.

---
**Review cadence**: Revisit this roadmap weekly or whenever a major feature flag ships. Update `PROJECT_STATUS_2025-11-24.md` only when milestones here graduate to "complete".
