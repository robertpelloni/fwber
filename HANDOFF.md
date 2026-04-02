# Handoff — Scene Discovery

**Date:** 2026-04-02  
**Status:** ✅ Local release verified  
**Version:** 1.0.47

## Overview
This cycle shipped the next scene-based discovery slice on top of the topic hubs release. Matches now rank and serialize shared scene overlap using followed topics plus visible social context from journals and public groups, and public profiles now expose a scene summary that makes those affinities legible outside the swipe deck. The implementation deliberately reuses the existing normalized interest, topic follow, journal visibility, and group-tag seams instead of building a second recommendation taxonomy.

## Accomplishments
- Extended `AIMatchingService` so the match engine now computes `scene_overlap` from followed topics, structured interests, public group metadata, and visible journal tags.
- Added `scene_summary` serialization to profile responses through `ProfileController` and `UserProfileResource`.
- Added backend regression coverage in `SceneDiscoveryFeatureTest` for both match-overlap and profile-summary payloads.
- Updated the swipe deck UI to render scene overlap cards and the public profile UI to render a dedicated scene summary block.
- Aligned the frontend `Match` client contract with the live payload (`is_confessional`, `voice_intro_url`, `age`, `gender`, `is_verified`, `photos`, scene overlap) so the typed frontend matches the shipped backend response.
- Bumped the repository release version from `1.0.46` to `1.0.47` and refreshed release-tracking docs.

## Key Files Modified
- `fwber-backend/app/Services/AIMatchingService.php`
- `fwber-backend/app/Http/Controllers/MatchController.php`
- `fwber-backend/app/Http/Controllers/ProfileController.php`
- `fwber-backend/app/Http/Resources/MatchResource.php`
- `fwber-backend/app/Http/Resources/UserProfileResource.php`
- `fwber-backend/tests/Feature/SceneDiscoveryFeatureTest.php`
- `fwber-frontend/app/matches/page.tsx`
- `fwber-frontend/app/profile/[id]/page.tsx`
- `fwber-frontend/lib/api/matches.ts`
- `fwber-frontend/lib/api/profile.ts`
- `VERSION`
- `package.json`
- `fwber-frontend/package.json`
- `CHANGELOG.md`
- `PROJECT_STATUS.md`
- `VERSION.md`
- `TODO.md`
- `ROADMAP.md`
- `HANDOFF.md`

## Validation
- `php artisan test tests/Feature/SceneDiscoveryFeatureTest.php tests/Feature/TopicHubFeatureTest.php`
- `npm run lint`
- `npm run build`
- fresh `npm run type-check`

## Notes / Risks
- Frontend lint still reports the pre-existing `react-hooks/exhaustive-deps` warning in `fwber-frontend/lib/api/photos.ts:476`.
- `fwber-frontend/tsconfig.tsbuildinfo` changes during frontend validation because it is tracked in git.
- The repo still has the known `.next` / `.next/types` contention when overlapping Next jobs hit the same checkout; direct shell builds can fail after successful compilation with missing build artifacts (`pages-manifest.json`, `_document.js`). Frontend validation remains reliable when run cleanly as lint -> clean build -> fresh type-check.
- Scene discovery currently affects match ranking/payloads and profile summaries. It does not yet reshape broader recommendation feeds or Local Pulse ranking.

## Next Recommended Slice
1. Extend the shipped **scene discovery** metadata into broader recommendation feeds and local loops beyond the swipe deck.
2. Fold relationship links, friend/circle visibility, and topic follows into a richer trust-aware discovery model without leaking private graph edges.
3. Keep building on the topic graph rather than adding a second parallel taxonomy system.
