# Handoff — Session Transfer Handoff Refresh

**Date:** 2026-04-02  
**Status:** ✅ Local release verified  
**Version:** 1.0.50

## Overview
This handoff captures the exact state of the repository after the `v1.0.49` code release. The project now has a coherent scene-discovery ladder running across matches, public profiles, recommendations, and Local Pulse cards, all built on the same followed-topic and normalized-scene-term graph. The most recent code changes shipped Local Pulse `scene_signals`, fixed a real Local Pulse schema mismatch in the candidate path, and eliminated the dashboard duplicate-logo overlap caused by the floating global subpage nav racing local-header detection. This `v1.0.50` update is purely to preserve those findings, the validated workflow, and the next recommended direction in enough detail for the next agent to resume without re-discovering the same terrain.

## Shipped Baseline
- **Latest pushed code release:** `v1.0.49`
- **Latest pushed commit on `origin/copilot-live-fix`:** `c84834649c02a8aa3b2d9b679ffb9ec96d99e8c0`
- **Latest documentation follow-up release:** `v1.0.50` (this handoff refresh)
- **Primary working checkout:** `C:\Users\hyper\.copilot\session-state\44f0d726-859c-45b4-aae1-b7f7a064bccf\files\fwber-live-fix`
- **Root workspace rule:** treat `C:\Users\hyper\workspace\fwber` as effectively read-only during safe recovery/release work

## What Was Completed
1. **Scene Discovery for Matches and Profiles (`v1.0.47`)**
   - Matches gained `scene_overlap`.
   - Public profiles gained `scene_summary`.
   - The frontend now renders shared scenes on swipe cards and public profile pages.
2. **Recommendation Scene Signals (`v1.0.48`)**
   - Recommendation items and feed cards gained `scene_signals`.
   - The scene matcher was hardened by tokenizing realistic content text instead of relying on whole-string normalization.
3. **Local Pulse Scene Signals (`v1.0.49`)**
   - Local Pulse artifacts and sponsored promotions now emit the same `scene_signals` contract.
   - Pulse cards now render scene-aligned headlines, matched topics, and matched tags.
   - The Local Pulse candidate query was corrected to use the codebase's actual `birthdate` field instead of stale `date_of_birth`.
4. **Shared Shell Fix (`v1.0.49`)**
   - `GlobalSubpageNav` now waits until header detection completes before rendering.
   - Dashboard routes are explicitly excluded, eliminating the duplicate floating-logo/header overlap.

## Key Technical Findings
- **The topic graph is now the canonical scene-discovery backbone.**
  - Reuse it rather than creating a second taxonomy.
  - Current scene-aware surfaces:
    - matches
    - public profiles
    - recommendations/feed cards
    - Local Pulse cards
- **Local Pulse still does not use scene signals for ranking.**
  - Right now it enriches artifact/promotion presentation only.
  - The next coherent technical step is to move scene signals from card metadata into feed ordering/ranking.
- **Trust-aware discovery is still intentionally incomplete.**
  - Relationship links, friendships, and circle visibility are already in place, but they are not yet folded into ranking logic.
  - Future work should preserve privacy by using those signals as ranking features without exposing private edges in API payloads.
- **A real backend bug was found during Local Pulse work.**
  - `ProximityArtifactController` still referenced `date_of_birth` in the nearby-candidate path.
  - The codebase actually uses `birthdate`.
  - The bug surfaced as a real 500 during the new Local Pulse scene test and is now fixed.
- **The dashboard overlap bug was a race, not a CSS-only spacing problem.**
  - The global floating nav was rendering before local-header detection finished.
  - This caused a second floating logo/nav shell to appear on top of the dashboard header.
  - The durable fix was to suppress rendering until detection completes, plus an explicit dashboard-route hide rule.

## Key Files Modified
- `fwber-backend/app/Services/AIMatchingService.php`
- `fwber-backend/app/Http/Controllers/ProximityArtifactController.php`
- `fwber-backend/tests/Feature/LocalPulseSceneSignalsTest.php`
- `fwber-frontend/components/LocalPulse.tsx`
- `fwber-frontend/types/proximity.ts`
- `fwber-frontend/components/GlobalSubpageNav.tsx`
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
- Scene discovery now affects matches, profiles, recommendation cards, and Local Pulse card metadata, but it still does not reshape Local Pulse ranking or deeper trust-aware recommendation inputs.
- Merchant lifecycle tooling, ActivityPub UI, and AI Wingman frontend wiring remain large unfinished areas compared with the more cohesive scene-discovery track.
- The safe-checkout workflow is still important because the environment is shared and the root repository may contain unrelated or user-owned changes.

## Next Recommended Slice
1. Extend the shipped **scene discovery** metadata from Local Pulse card cues into local-feed ranking and trust-aware recommendation inputs.
2. Fold relationship links, friend/circle visibility, and topic follows into a richer trust-aware discovery model without leaking private graph edges.
3. Keep building on the topic graph rather than adding a second parallel taxonomy system.

## Suggested Resume Procedure
1. Start from `origin/copilot-live-fix` after `c84834649c02a8aa3b2d9b679ffb9ec96d99e8c0` plus this documentation refresh.
2. Re-read `HANDOFF.md`, `PROJECT_STATUS.md`, `ROADMAP.md`, `TODO.md`, and the session `plan.md`.
3. Stay in the session-state checkout and avoid changing the root workspace copy.
4. Create a new SQL todo for the next slice before implementing.
5. Reuse the established validation order and avoid overlapping Next jobs in the same checkout.
