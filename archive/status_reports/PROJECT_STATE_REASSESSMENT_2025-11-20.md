# Project State Reassessment — 2025-11-20

_Primary author: GitHub Copilot (GPT-5.1-Codex)_

## Executive Summary
- **MVP baseline remains stable** across Auth, Profiles, Matches, and Proximity flows; recent Next.js lint + curl smoke checks passed, though Proximity Cypress specs are currently red (see Risks).
- **Privacy & safety focus** advanced with client-side face blur beta (preview compare UX, Cache Storage weight caching) and backend telemetry persistence via `telemetry_events`.
- **Telemetry visibility is the new bottleneck**: data lands in MySQL but has no first-class analytics surface, blocking privacy reviewers from measuring blur adoption or opt-out trends.

## Current Pillar Snapshot
| Pillar | Status | Notes |
| --- | --- | --- |
| Auth / Profile / Matches | ✅ | Laravel token/auth routes unchanged; Cypress `matching-flow` green in last recorded run.
| Messaging / Dashboard | ✅ | No new regressions; SSE/websocket features remain gated.
| Photos & Privacy | ⚠️ Beta | Face blur beta gated by `NEXT_PUBLIC_FEATURE_CLIENT_FACE_BLUR`; server still enforces avatar-only uploads in production.
| Telemetry & Analytics | ⚠️ Needs surface | `TelemetryService` validates + stores events but there's no reporting endpoint/dashboards.
| Gamification (Face Reveal) | 🚧 Not started | Flags exist (`FEATURE_FACE_REVEAL`) but only documentation spikes are tracked.
| Local Vault | 🚧 Not started | Architecture spike exists; no code paths yet.

## Key Observations
1. **Face blur preview instrumentation is rich**—`face_blur_preview_{ready,toggled,discarded}` plus upload-stage events give full funnel coverage when `TELEMETRY_STORE_EVENTS` is enabled.
2. **Data accessibility gap**—Moderators/reviewers have no JSON API or dashboard to query adoption metrics, forcing them to hit the database manually.
3. **Cypress proximity suite failing**—`cypress/e2e/proximity-feed.cy.js` exited 1 in the latest terminal history; needs follow-up after analytics work.
4. **Roadmap alignment**—Docs still call for analytics and feature spikes; with telemetry persistence done, the natural next increment is an observer-friendly summary API that closes the feedback loop.

## Recommended Immediate Actions
1. **Ship a preview telemetry analytics endpoint (today)**
   - Scope: Summarize `face_blur_preview_*` + upload events over 1d/7d/30d ranges (counts, blur adoption %, discard reasons, top warnings).
   - Guard with `feature:analytics` + moderator auth.
   - Cache responses briefly (60s) to keep load low.
2. **Document & socialize analytics workflow**
   - Update docs to explain how to enable `FEATURE_ANALYTICS` and hit the new endpoint (e.g., via `GET /api/telemetry/preview-summary`).
3. **Next sprint**: extend telemetry hook to onboarding + DM uploaders, then revisit Face Reveal / Local Vault spikes once privacy KPIs are observable.

## Plan for this Session
- Build the feature-flagged analytics endpoint + tests. ✅ `GET /api/telemetry/preview-summary`
- Update documentation + `.env.example` references. ✅ Added to `docs/implementation/SEX_PRIVACY_FEATURES.md` and `docs/FEATURE_FLAGS.md`
- Leave a note to investigate the Proximity Cypress regression once analytics is live.
