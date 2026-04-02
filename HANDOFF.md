# Handoff — Trust-Aware Local Pulse Ranking

**Date:** 2026-04-02  
**Status:** ✅ Local release verified  
**Version:** 1.0.51

## Overview
This cycle moved Local Pulse from scene-aware presentation into privacy-safe trust-aware ranking. The local feed now orders artifacts with a composite of trusted connections, scene alignment, and freshness, while still keeping friendships, relationship links, and shared-circle membership internal to scoring rather than exposing them as payload details. The implementation deliberately reuses the same topic/scene graph and existing privacy/trust helpers already established across matches, profiles, journals, and relationship links.

## Shipped Baseline
- **Latest pushed code release:** `v1.0.51`
- **Previous baseline before this slice:** `v1.0.50`
- **Primary working checkout:** `C:\Users\hyper\.copilot\session-state\44f0d726-859c-45b4-aae1-b7f7a064bccf\files\fwber-live-fix`
- **Root workspace rule:** treat `C:\Users\hyper\workspace\fwber` as effectively read-only during safe recovery/release work

## What Was Completed
1. **Trust-Aware Local Pulse Ranking (`v1.0.51`)**
   - Added `LocalPulseRankingService` to batch trust signals from accepted friendships, confirmed relationship links, and shared active circles.
   - Reworked `ProximityArtifactController::localPulse()` so artifacts are ordered by a composite of trust, scene alignment, and freshness.
   - Kept trust signals internal to ordering; the payload only exposes a high-level `ranking_strategy` explanation.
   - Added regression coverage proving a trusted, scene-aligned artifact outranks a newer generic stranger post.
2. **Previously Shipped Scene Discovery Stack**
   - `v1.0.47`: matches + profiles
   - `v1.0.48`: recommendations
   - `v1.0.49`: Local Pulse scene signals + dashboard shell fix
   - `v1.0.50`: documentation/session-transfer refresh

## Key Technical Findings
- **The topic graph is now the canonical scene-discovery backbone.**
  - Reuse it rather than creating a second taxonomy.
  - Current scene-aware surfaces:
    - matches
    - public profiles
    - recommendations/feed cards
    - Local Pulse cards
    - Local Pulse ranking
- **The safest trust-aware ranking design is internal scoring, not richer payload graph data.**
  - Friendship, confirmed relationship links, and shared active circles are now used only as server-side ranking inputs.
  - The feed explains ranking strategy at a high level without serializing private relationship context.
- **Trust-aware discovery is still intentionally incomplete.**
  - Local Pulse ranking now uses privacy-safe trust signals, but recommendations and other local-feed loops still do not.
  - Future work should extend the same pattern into recommendation ranking without exposing private edges in API payloads.
- **A real backend bug was found during Local Pulse work.**
  - `ProximityArtifactController` still referenced `date_of_birth` in the nearby-candidate path.
  - The codebase actually uses `birthdate`.
  - The bug surfaced as a real 500 during the new Local Pulse scene test and is now fixed.
- **The dashboard overlap bug was a race, not a CSS-only spacing problem.**
  - The global floating nav was rendering before local-header detection finished.
  - This caused a second floating logo/nav shell to appear on top of the dashboard header.
  - The durable fix was to suppress rendering until detection completes, plus an explicit dashboard-route hide rule.

## Key Files Modified
- `fwber-backend/app/Services/LocalPulseRankingService.php`
- `fwber-backend/app/Services/AIMatchingService.php`
- `fwber-backend/app/Http/Controllers/ProximityArtifactController.php`
- `fwber-backend/tests/Feature/LocalPulseSceneSignalsTest.php`
- `fwber-frontend/components/LocalPulse.tsx`
- `fwber-frontend/types/proximity.ts`
- `VERSION`
- `package.json`
- `fwber-frontend/package.json`
- `CHANGELOG.md`
- `PROJECT_STATUS.md`
- `VERSION.md`
- `TODO.md`
- `ROADMAP.md`
- `HANDOFF.md`

## Validation Workflow That Proved Reliable
- **Backend**
  - `php artisan test tests/Feature/LocalPulseSceneSignalsTest.php tests/Feature/TopicHubFeatureTest.php tests/Feature/SceneDiscoveryFeatureTest.php`
- **Frontend**
  1. `npm run lint`
  2. clean `npm run build`
  3. fresh `npm run type-check`

## Validation Notes
- Frontend lint still reports the pre-existing `react-hooks/exhaustive-deps` warning in `fwber-frontend/lib/api/photos.ts:476`.
- `fwber-frontend/tsconfig.tsbuildinfo` changes during frontend validation because it is tracked in git.
- The shared checkout can still hit `.next` / `.next/types` contention if overlapping Next jobs run in the same tree.
- When direct shell builds look suspicious, a clean isolated validation pass is the trustworthy signal.

## Notes / Risks
- The repo still has the known `.next` / `.next/types` contention when overlapping Next jobs hit the same checkout; direct shell builds can fail after successful compilation with missing build artifacts like `pages-manifest.json` or `_document.js`.
- Scene discovery now affects matches, profiles, recommendation cards, Local Pulse card metadata, and Local Pulse ranking, but it still does not reshape deeper trust-aware recommendation inputs.
- Merchant lifecycle tooling, ActivityPub UI, and AI Wingman frontend wiring remain large unfinished areas compared with the more cohesive scene-discovery track.
- The safe-checkout workflow is still important because the environment is shared and the root repository may contain unrelated or user-owned changes.

## Next Recommended Slice
1. Extend the same privacy-safe trust-aware scoring model from Local Pulse ranking into recommendation ranking and related local-feed loops.
2. Fold relationship links, friend/circle visibility, and topic follows into a richer trust-aware discovery model without leaking private graph edges.
3. Keep building on the topic graph rather than adding a second parallel taxonomy system.

## Suggested Resume Procedure
1. Start from `origin/copilot-live-fix` after `c84834649c02a8aa3b2d9b679ffb9ec96d99e8c0` plus this documentation refresh.
2. Re-read `HANDOFF.md`, `PROJECT_STATUS.md`, `ROADMAP.md`, `TODO.md`, and the session `plan.md`.
3. Stay in the session-state checkout and avoid changing the root workspace copy.
4. Create a new SQL todo for the next slice before implementing.
5. Reuse the established validation order and avoid overlapping Next jobs in the same checkout.
