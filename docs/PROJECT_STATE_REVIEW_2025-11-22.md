# Project State Review — 2025-11-22

_Primary author: GitHub Copilot (GPT-5.1-Codex)_

## 1. Executive Summary
- **MVP scope** (Auth, Profile, Dashboard, Matches, Direct Messages, Photos, Safety, Physical Profile, Location, Proximity Artifacts) remains stable and continues to pass the latest manual checks.
- **Recent accomplishments** include the dynamic `SexQuote` surface (`fwber-frontend/components/SexQuote.tsx`), Cypress coverage for core journeys (matching, messaging, physical profile, proximity feed), a local SVG avatar for deterministic tests (`fwber-frontend/public/images/test-avatar.svg`), and a Sentry migration to modern App Router instrumentation (`fwber-frontend/instrumentation.ts`).
- **Privacy focus**: A client-side face blur beta now runs behind `NEXT_PUBLIC_FEATURE_CLIENT_FACE_BLUR`, enriching uploads with structured metadata that the backend ingests via `PhotoController::emitFaceBlurTelemetry()`.
- **Primary risks**: (1) Face-blur telemetry only fires when uploads finish—no visibility into opt-outs or failures; (2) Phase-3 roadmap items (Face Reveal game, encrypted vault) still lack spikes.

## 2. Core Pillar Status
| Pillar | Status | Evidence / Notes |
| --- | --- | --- |
| Auth | ✅ Stable | Laravel guards + token issuance unchanged; no regressions reported in `tests/Feature/Auth*` suites.
| Profile | ✅ Stable | Profile editor React components untouched; Cypress `physical-profile.cy.js` still green.
| Dashboard | ✅ Stable | No open defects; `Dashboard` routes continue to hydrate mock data.
| Matches | ✅ Stable | Matching flow Cypress suite covers swipe + accept/reject; backend `MatchController` unchanged.
| Direct Messages | ✅ Stable | Messaging Cypress spec validates send/receive stub; Mercure/WebSocket features remain gated.
| Photos | ⚠️ Beta | Upload path supported, but avatar-only production flag (`config('app.avatar_mode')`) still blocks real uploads; client blur beta behind env flag.
| Safety | ⚠️ Partial | Telemetry + moderation scaffolding exist but are mostly gated by feature flags (`config/features.php`).
| Physical Profile | ✅ Stable | Frontend forms + validations tested; no backend schema drift.
| Location & Proximity Artifacts | ✅/⚠️ | Feed endpoints available under `FEATURE_PROXIMITY_ARTIFACTS`; Cypress `proximity-feed.cy.js` provides regression cover, but artifacts moderation tooling still gated.

## 3. Recent Improvements Snapshot
1. **Deterministic Cypress assets** — `public/images/test-avatar.svg` eliminates remote fetches during tests.
2. **Automation** — `cypress/e2e/*` suites for matching, messaging, physical profile, and proximity feed run via `start-server-and-test`.
3. **Sentry modernization** — `instrumentation.ts` + client hook `instrumentation-client.ts` wire Sentry to Next.js 14 router events.
4. **Face blur metadata** — `components/PhotoUpload.tsx`, `lib/faceBlur.ts`, and `lib/faceBlurTelemetry.ts` annotate uploads with `facesDetected`, `blurApplied`, and skip reasons which travel to `PhotoController::emitFaceBlurTelemetry()`.

## 4. Gap Analysis & Risks
| Area | Gap | Recommended Mitigation |
| --- | --- | --- |
| Face blur UX | Users cannot compare blurred vs. original previews, making trust-building difficult for the beta. | Add dual preview URLs + toggle controls inside `PhotoUpload`. Surface warnings inline.
| Telemetry visibility | Only successful uploads produce events; skipped previews that users delete are invisible. | Extend telemetry plan to emit client-side events (or collect counts) before upload completion.
| Roadmap alignment | Roadmap doc still lists face blur as "Researching" and lacks up-to-date statuses. | Refresh `docs/roadmap/ROADMAP_EXPANSION_2025-11-20.md` with new reality + next milestones.
| Future features | No technical spikes yet for "Face Reveal" or "Local-First Vault". | Schedule research tickets following blur UX improvements; define feature flags now to reduce later churn.

## 5. Recommended Next Steps
1. **Finalize client blur beta experience (Current sprint)**
   - ✅ Capture both original and blurred object URLs per preview.
   - ✅ Provide a unified before/after comparison (thumbnail toggle + slider modal) so users can visually verify the blur.
   - ✅ Revoke all object URLs on cleanup to avoid leaks.
   - Document how to enable the beta and interpret warnings.
2. **Update roadmap + docs (Current sprint)**
   - Mark SexQuote as complete, face blur as "Beta behind env flag".
   - Add follow-up tasks: client telemetry enhancements, model caching, preview comparison.
3. **Plan upcoming features (Next sprint)**
   - Draft spike docs for "Face Reveal + Auto Reply" loops and for the "Local Media Vault" flag/architecture.
   - Define new feature flags (`FEATURE_FACE_REVEAL`, `FEATURE_LOCAL_MEDIA_VAULT`) even if disabled by default.

## 6. Action Items Logged
- [x] Implement blur comparison UX in `PhotoUpload` (see Section 5.1).
- [ ] Update roadmap doc per Section 5.2.
- [ ] Add beta documentation describing enabling `NEXT_PUBLIC_FEATURE_CLIENT_FACE_BLUR` and expected telemetry signals.
- [ ] Prepare spike outlines for Face Reveal game + encrypted vault (post-beta).

_This review supersedes the November 15 agent findings and should be revisited after the blur beta ships to production testers._
