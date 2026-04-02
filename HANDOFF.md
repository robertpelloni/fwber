# Handoff — Topic Hubs

**Date:** 2026-04-02  
**Status:** ✅ Local release verified  
**Version:** 1.0.46

## Overview
This cycle shipped the next structured-interest slice after journals and relationship links: canonical topic hubs. Users can now browse and follow topics, open hub detail pages that aggregate public groups plus visibility-safe journals and topic-scoped Local Pulse artifacts, and filter or post Local Pulse entries against a selected topic. The implementation deliberately reuses the existing normalized interest, journal visibility, and Local Pulse seams instead of introducing a duplicate graph.

## Accomplishments
- Added the backend `Topic` model, seeded `topics` catalog migration, `topic_user_follows` pivot, topic controller/resource, and authenticated topic routes.
- Extended Local Pulse validation and controller flows so create/feed/local-pulse requests accept an optional `topic_slug` and persist/filter that value through artifact `meta`.
- Added backend feature coverage for follow/unfollow, hub aggregation, and topic-scoped proximity feed filtering.
- Added new frontend topic API/hooks/components plus `/topics` and `/topics/[slug]` pages, and surfaced topics in the main app navigation.
- Extended Local Pulse and group cards so topics are visible in the UI and users can browse scene-specific activity directly from a hub.
- Bumped the repository release version from `1.0.45` to `1.0.46` and refreshed release-tracking docs.

## Key Files Modified
- `fwber-backend/app/Models/Topic.php`
- `fwber-backend/app/Http/Controllers/TopicController.php`
- `fwber-backend/app/Http/Resources/TopicResource.php`
- `fwber-backend/database/migrations/2026_04_02_071745_create_topics_table.php`
- `fwber-backend/tests/Feature/TopicHubFeatureTest.php`
- `fwber-backend/app/Http/Controllers/ProximityArtifactController.php`
- `fwber-backend/app/Http/Requests/StoreProximityArtifactRequest.php`
- `fwber-backend/app/Http/Requests/ProximityFeedRequest.php`
- `fwber-backend/app/Http/Requests/LocalPulseRequest.php`
- `fwber-frontend/app/topics/page.tsx`
- `fwber-frontend/app/topics/[slug]/page.tsx`
- `fwber-frontend/components/LocalPulse.tsx`
- `fwber-frontend/components/TopicCard.tsx`
- `fwber-frontend/lib/api/topics.ts`
- `fwber-frontend/lib/hooks/use-topics.ts`
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
- `php artisan test tests/Feature/TopicHubFeatureTest.php`
- `npm run lint`
- `npm run build`
- fresh `npm run type-check`

## Notes / Risks
- Frontend lint still reports the pre-existing `react-hooks/exhaustive-deps` warning in `fwber-frontend/lib/api/photos.ts:476`.
- `fwber-frontend/tsconfig.tsbuildinfo` changes during frontend validation because it is tracked in git.
- The repo still has the known `.next/types` contention when build and type-check overlap in the same checkout; frontend validation remains reliable when run as lint -> build -> fresh type-check.
- Topic hubs currently aggregate groups, journals, and Local Pulse artifacts. They do not yet re-rank the main matches feed or profile cards with followed-topic affinity.

## Next Recommended Slice
1. Extend **scene-based discovery** into the matches feed and profile recommendations using followed topics and shared hub activity.
2. Add richer profile-level scene cards so topic follows, visible journals, and group participation reinforce each other.
3. Keep building on the topic graph rather than adding a second parallel taxonomy system.
