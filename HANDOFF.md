# Handoff — Relationship Links

**Date:** 2026-04-02  
**Status:** ✅ Local release verified  
**Version:** 1.0.45

## Overview
This cycle shipped the next social-graph slice after journals: consensual relationship links between accepted friends. Users can now propose a relationship/status link, wait for the other friend to confirm it, control whether it is `public`, `friends`, or `private`, manage those links from Friends & Connections, and render visible links directly on public profiles. The implementation deliberately reuses the existing friendship graph and the new visibility-profile seams from the journals release.

## Accomplishments
- Added the backend `RelationshipLink` model, migration, controller, resource, and relationship-link routes.
- Extended `ContentVisibilityService`, `ProfileController`, and `UserProfileResource` so visible confirmed links are serialized onto profiles with friend-aware gating.
- Added backend feature coverage for friend-only proposal rules, confirmation flow, friend-scoped visibility, and cleanup when a friendship is removed.
- Added a new relationship-link API client and expanded the Friends & Connections UI to create, review, edit, and remove relationship links.
- Extended public profile rendering so visible relationship links appear directly on profile pages alongside journals.
- Bumped the repository release version from `1.0.44` to `1.0.45` and refreshed release-tracking docs.

## Key Files Modified
- `fwber-backend/app/Models/RelationshipLink.php`
- `fwber-backend/app/Http/Controllers/RelationshipLinkController.php`
- `fwber-backend/app/Http/Resources/RelationshipLinkResource.php`
- `fwber-backend/database/migrations/2026_04_02_071500_create_relationship_links_table.php`
- `fwber-backend/tests/Feature/RelationshipLinkFeatureTest.php`
- `fwber-backend/app/Services/ContentVisibilityService.php`
- `fwber-backend/app/Http/Controllers/FriendController.php`
- `fwber-frontend/app/friends/page.tsx`
- `fwber-frontend/app/profile/[id]/page.tsx`
- `fwber-frontend/lib/api/relationships.ts`
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
- `php artisan test tests/Feature/RelationshipLinkFeatureTest.php tests/Feature/FriendTest.php`
- `npm run lint`
- `npm run type-check`
- `npm run build`

## Notes / Risks
- Frontend lint still reports the pre-existing `react-hooks/exhaustive-deps` warning in `fwber-frontend/lib/api/photos.ts:476`.
- `fwber-frontend/tsconfig.tsbuildinfo` changes during frontend validation because it is tracked in git.
- The first concurrent `npm run type-check` again failed because the repo still has the known `.next/types` contention when build and type-check overlap in the same checkout; a fresh post-build `npm run type-check` passed cleanly.
- Relationship links are intentionally constrained to accepted friends in this MVP. If the product later wants looser consent flows or multi-party/circle relationship structures, that should build on this confirmed two-party base instead of bypassing it.

## Next Recommended Slice
1. Build **topic hubs** that connect journals, Local Pulse, groups, and the new relationship metadata around structured interests.
2. Extend discovery beyond proximity with **scene-based interest discovery** using journals + relationship links as richer social context.
3. Add richer profile-level social graph descriptors only after the hub/discovery layer decides what metadata is most useful.
