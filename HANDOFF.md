# Handoff — Trust-Aware Chatroom Ranking & Sidebar Shell Sweep

**Date:** 2026-04-02  
**Status:** ✅ Local release verified  
**Version:** 1.0.61

## Overview
This cycle shipped two connected pieces of work. The main chatroom browse directory now uses the same privacy-safe trust-aware ranking model already established across Local Pulse, recommendations, nearby chatrooms, events, bulletin boards, group matching, venues, nearby users, audio rooms, and deals. In parallel, the remaining requested community and discovery pages were moved onto the shared `AppHeader` shell so the left desktop sidebar appears consistently across the app, with `/federation` promoted to the primary federation entry route.

## Shipped Baseline
- **Latest pushed code release:** `v1.0.61`
- **Previous baseline before this slice:** `v1.0.50`
- **Primary working checkout:** `C:\Users\hyper\.copilot\session-state\44f0d726-859c-45b4-aae1-b7f7a064bccf\files\fwber-live-fix`
- **Root workspace rule:** treat `C:\Users\hyper\workspace\fwber` as effectively read-only during safe recovery/release work

## What Was Completed
1. **Trust-Aware Chatroom Ranking (`v1.0.61`)**
   - Added `ChatroomRankingService` to rank `GET /api/chatrooms` browse results with the same privacy-safe trust map already used by Local Pulse, recommendations, nearby chatrooms, events, bulletin boards, group matching, venues, nearby users, audio rooms, and deals.
   - Reused `LocalPulseRankingService` for creator trust scoring and `AIMatchingService` for scene-signal enrichment so chatroom browse now reflects trusted creators, scene alignment, community health, and freshness.
   - Extended the ranked chatroom response contract with high-level `meta.ranking_strategy`, scene cues, and ranking scores while preserving privacy boundaries and existing browse behavior.
   - Added focused regression coverage proving chatrooms expose ranking metadata and that a trusted, scene-aligned room can outrank a busier generic room.
2. **Shared Sidebar Shell Sweep**
   - Moved groups, events, proximity chatrooms, conference pulse, date planner, audio rooms, burner bridge, bulletin boards, nearby, leaderboard, and federation onto the shared `AppHeader` shell so the desktop sidebar is consistent.
   - Promoted `/federation` to the main navigation route while preserving the existing settings federation page implementation behind the new route alias.
   - Normalized loading and error states on the updated pages so the sidebar remains visible outside just the happy path.
3. **Previously Shipped Scene Discovery Stack**
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
    - `v1.0.61`: chatroom trust-aware ranking + sidebar shell sweep

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
    - chatroom browse ranking
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
- `fwber-backend/app/Http/Controllers/ChatroomController.php`
- `fwber-backend/app/Services/ChatroomRankingService.php`
- `fwber-backend/tests/Feature/ChatroomRankingTest.php`
- `fwber-frontend/app/chatrooms/page.tsx`
- `fwber-frontend/lib/api/chatrooms.ts`
- `fwber-frontend/components/AppHeader.tsx`
- `fwber-frontend/app/groups/page.tsx`
- `fwber-frontend/app/events/page.tsx`
- `fwber-frontend/app/proximity-chatrooms/page.tsx`
- `fwber-frontend/app/conference-pulse/page.tsx`
- `fwber-frontend/app/date-planner/page.tsx`
- `fwber-frontend/app/(protected)/audio-rooms/page.tsx`
- `fwber-frontend/app/(protected)/burner/page.tsx`
- `fwber-frontend/components/pages/BulletinBoardsPageClient.tsx`
- `fwber-frontend/app/nearby/page.tsx`
- `fwber-frontend/app/leaderboard/page.tsx`
- `fwber-frontend/app/settings/federation/page.tsx`
- `fwber-frontend/app/federation/page.tsx`
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
  - `php artisan test tests/Feature/ChatroomRankingTest.php`
- **Frontend**
  1. `npm run lint`
  2. `npm run type-check`
  3. clean `npm run build`

## Validation Notes
- Frontend lint still reports the pre-existing `react-hooks/exhaustive-deps` warning in `fwber-frontend/lib/api/photos.ts:476`.
- `fwber-frontend/tsconfig.tsbuildinfo` changes during frontend validation because it is tracked in git.
- The shared checkout can still hit `.next` / `.next/types` contention if overlapping Next jobs run in the same tree.
- When direct shell builds look suspicious, a clean isolated validation pass is the trustworthy signal.

## Notes / Risks
- The repo still has the known `.next` / `.next/types` contention when overlapping Next jobs hit the same checkout; clean isolated builds remain the trustworthy signal.
- The desktop sidebar is provided by `AppHeader` and shared shell CSS, not by page-local aside markup. Future sidebar fixes should move pages onto that shared shell rather than duplicating left-rail UI.
- Scene discovery now affects matches, profiles, recommendation cards, recommendation ranking, nearby chatroom ranking, chatroom browse ranking, event ranking, bulletin board ranking, group matching ranking, venue ranking, nearby-user ranking, audio-room ranking, deal ranking, Local Pulse card metadata, and Local Pulse ranking, but it still does not reshape every remaining local-feed surface.
- Merchant lifecycle tooling, deeper ActivityPub UI, and AI Wingman frontend wiring remain large unfinished areas compared with the more cohesive scene-discovery track.
- The safe-checkout workflow is still important because the environment is shared and the root repository may contain unrelated or user-owned changes.

## Next Recommended Slice
1. Extend the same privacy-safe trust-aware scoring model from Local Pulse, recommendations, nearby chatrooms, chatroom browse, event ranking, bulletin board ranking, group matching ranking, venue ranking, nearby-user ranking, audio-room ranking, and deal ranking into the remaining local-feed and discovery loops.
2. Fold relationship links, friend/circle visibility, and topic follows into a richer trust-aware discovery model without leaking private graph edges.
3. Keep building on the topic graph rather than adding a second parallel taxonomy system.

## Suggested Resume Procedure
1. Start from `origin/copilot-live-fix` after `v1.0.61`.
2. Re-read `HANDOFF.md`, `PROJECT_STATUS.md`, `ROADMAP.md`, `TODO.md`, and the session `plan.md`.
3. Stay in the session-state checkout and avoid changing the root workspace copy.
4. Create a new SQL todo for the next slice before implementing.
5. Reuse the established validation order and avoid overlapping Next jobs in the same checkout.
