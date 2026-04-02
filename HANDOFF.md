# Handoff — Trust-Aware Deal Ranking

**Date:** 2026-04-02  
**Status:** ✅ Local release verified  
**Version:** 1.0.60

## Overview
This cycle moved nearby deal discovery onto the same privacy-safe trust-aware ranking model already established in Local Pulse, recommendations, nearby chatrooms, events, bulletin boards, group matching, venues, nearby users, and audio rooms. Deal results now balance trusted merchants, scene alignment, deal health, freshness, and distance while still keeping friendships, relationship links, and shared-circle membership internal to scoring rather than exposing them as payload details. The implementation also fixed the directly coupled `merchant` versus `merchantProfile` browse-path mismatch so the deals API and UI now share one stable merchant contract.

## Shipped Baseline
- **Latest pushed code release:** `v1.0.60`
- **Previous baseline before this slice:** `v1.0.50`
- **Primary working checkout:** `C:\Users\hyper\.copilot\session-state\44f0d726-859c-45b4-aae1-b7f7a064bccf\files\fwber-live-fix`
- **Root workspace rule:** treat `C:\Users\hyper\workspace\fwber` as effectively read-only during safe recovery/release work

## What Was Completed
1. **Trust-Aware Deal Ranking (`v1.0.60`)**
   - Added `DealRankingService` to rank `GET /api/deals` results with the same privacy-safe trust map already used by Local Pulse, recommendations, nearby chatrooms, events, bulletin boards, group matching, venues, nearby users, and audio rooms.
   - Reused `LocalPulseRankingService` for merchant trust scoring, `AIMatchingService` for scene-signal enrichment, and geo distance scoring so deals now reflect trusted merchants, scene alignment, deal health, freshness, and distance.
   - Extended the ranked deals response contract with paginated `data`, convenience `deals`, and `meta.ranking_strategy` while preserving pagination behavior for existing consumers.
   - Added focused regression coverage proving deals expose ranking metadata and that a trusted, scene-aligned merchant offer can outrank a slightly closer generic deal.
2. **Coupled Browse-Path Contract Fix**
   - Added a canonical `merchant()` alias on `Promotion` so `MerchantController::browseDeals()` no longer depends on a missing relation name.
   - Aligned the deals query to load the merchant fields required by both the ranker and UI, including verification state and merchant-owned scene text.
2. **Previously Shipped Scene Discovery Stack**
    - `v1.0.47`: matches + profiles
    - `v1.0.48`: recommendations
    - `v1.0.49`: Local Pulse scene signals + dashboard shell fix
    - `v1.0.50`: documentation/session-transfer refresh
    - `v1.0.51`: Local Pulse trust-aware ranking
    - `v1.0.52`: recommendation trust-aware ranking
    - `v1.0.53`: nearby chatroom trust-aware ranking
    - `v1.0.54`: event trust-aware ranking
    - `v1.0.55`: bulletin board trust-aware ranking
    - `v1.0.56`: group matching trust-aware ranking
    - `v1.0.57`: venue trust-aware ranking
    - `v1.0.58`: nearby-user trust-aware ranking
    - `v1.0.59`: audio-room trust-aware ranking
    - `v1.0.60`: deal trust-aware ranking

## Key Technical Findings
- **The topic graph is now the canonical scene-discovery backbone.**
  - Reuse it rather than creating a second taxonomy.
  - Current scene-aware surfaces:
    - matches
    - public profiles
    - recommendations/feed cards
    - recommendation ranking
    - nearby chatroom ranking
    - event ranking
    - bulletin board ranking
    - group matching ranking
    - venue ranking
    - nearby user ranking
    - audio-room ranking
    - deal ranking
    - Local Pulse cards
    - Local Pulse ranking
- **The safest trust-aware ranking design is internal scoring, not richer payload graph data.**
  - Friendship, confirmed relationship links, and shared active circles are now used only as server-side ranking inputs.
  - The feed explains ranking strategy at a high level without serializing private relationship context.
- **Trust-aware discovery is still intentionally incomplete.**
  - Local Pulse, recommendations, nearby chatroom ranking, event ranking, bulletin board ranking, group matching ranking, venue ranking, nearby-user ranking, audio-room ranking, and deal ranking now use privacy-safe trust signals, but other local-feed and discovery loops still do not.
  - Future work should extend the same pattern into the remaining discovery surfaces without exposing private edges in API payloads.
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
- `fwber-backend/app/Services/DealRankingService.php`
- `fwber-backend/app/Http/Controllers/MerchantController.php`
- `fwber-backend/app/Http/Requests/Merchant/BrowseDealsRequest.php`
- `fwber-backend/app/Models/Promotion.php`
- `fwber-backend/tests/Feature/DealRankingTest.php`
- `fwber-backend/tests/Feature/MerchantPromotionCrudTest.php`
- `fwber-backend/tests/Feature/MerchantPulseTest.php`
- `fwber-backend/app/Services/AIMatchingService.php`
- `fwber-backend/app/Services/LocalPulseRankingService.php`
- `fwber-frontend/app/deals/page.tsx`
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
  - `php artisan test tests/Feature/MerchantPromotionCrudTest.php tests/Feature/MerchantPulseTest.php tests/Feature/DealRankingTest.php`
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
- Scene discovery now affects matches, profiles, recommendation cards, recommendation ranking, nearby chatroom ranking, event ranking, bulletin board ranking, group matching ranking, venue ranking, nearby-user ranking, audio-room ranking, deal ranking, Local Pulse card metadata, and Local Pulse ranking, but it still does not reshape every remaining local-feed surface.
- Merchant lifecycle tooling, ActivityPub UI, and AI Wingman frontend wiring remain large unfinished areas compared with the more cohesive scene-discovery track.
- The safe-checkout workflow is still important because the environment is shared and the root repository may contain unrelated or user-owned changes.

## Next Recommended Slice
1. Extend the same privacy-safe trust-aware scoring model from Local Pulse, recommendations, nearby chatrooms, event ranking, bulletin board ranking, group matching ranking, venue ranking, nearby-user ranking, audio-room ranking, and deal ranking into the remaining local-feed and discovery loops.
2. Fold relationship links, friend/circle visibility, and topic follows into a richer trust-aware discovery model without leaking private graph edges.
3. Keep building on the topic graph rather than adding a second parallel taxonomy system.

## Suggested Resume Procedure
1. Start from `origin/copilot-live-fix` after `v1.0.60`.
2. Re-read `HANDOFF.md`, `PROJECT_STATUS.md`, `ROADMAP.md`, `TODO.md`, and the session `plan.md`.
3. Stay in the session-state checkout and avoid changing the root workspace copy.
4. Create a new SQL todo for the next slice before implementing.
5. Reuse the established validation order and avoid overlapping Next jobs in the same checkout.
