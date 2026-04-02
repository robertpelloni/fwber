# Handoff — Bounty Flow Repair

**Date:** 2026-04-02  
**Status:** ✅ Local release verified  
**Version:** 1.0.39

## Overview
This cycle repaired the production bounty flow regressions reported from the live site. The fix aligned the bounty API payload with what the frontend actually renders, removed dead bounty navigation targets, and moved the authenticated create/suggest surfaces onto the live `/api/bounties` contract while preserving public shareable bounty detail pages.

## Accomplishments
- Expanded `Api\MatchBountyController` eager loading so `/api/bounties` includes `user.profile` and `user.photos`, matching the live bounty card UI.
- Added a `UserProfile` `age` accessor so serialized profile payloads include the age field both bounty surfaces already expect.
- Added focused `MatchBountyTest` coverage for the bounty index route, including the frontend's `sort=reward` alias and the nested relation payload shape.
- Switched `CreateBountyModal` to `POST /api/bounties` and kept the returned share URL behavior intact.
- Replaced the dead `/home` and `/profile/bounty/create` links on the bounty list page with `/dashboard` and the existing reusable bounty creation modal.
- Moved authenticated bounty suggestions to `/api/bounties/{slug}/suggest` while keeping public bounty detail fetches on the public legacy show route.

## Key Files Modified
- `fwber-backend/app/Http/Controllers/Api/MatchBountyController.php`
  - Added the richer bounty eager-loading contract, accepted the frontend `sort=reward` alias, and returned `share_url` from the modern create endpoint.
- `fwber-backend/app/Models/UserProfile.php`
  - Added an appended `age` accessor for serialized profile payloads.
- `fwber-backend/tests/Feature/MatchBountyTest.php`
  - Added regression coverage for the live bounty index payload and moved create coverage onto `/api/bounties`.
- `fwber-frontend/app/bounties/page.tsx`
  - Fixed the dead back/create navigation and reused the existing create-bounty modal directly from the bounty list page.
- `fwber-frontend/app/bounty/[slug]/page.tsx`
  - Moved authenticated suggestions onto the live `/api/bounties/{slug}/suggest` route.
- `fwber-frontend/components/CreateBountyModal.tsx`
  - Switched creation to `/api/bounties` and made the trigger styling reusable across screens.
- `VERSION`
- `package.json`
- `fwber-frontend/package.json`
- `CHANGELOG.md`
- `PROJECT_STATUS.md`
- `VERSION.md`
- `TODO.md`
- `ROADMAP.md`

## Validation
- `php artisan test tests/Feature/MatchBountyTest.php`
- `npm run lint`
- `npm run type-check`
- `npm run build`

## Notes / Risks
- Frontend lint still reports the pre-existing `react-hooks/exhaustive-deps` warning in `fwber-frontend/lib/api/photos.ts:476`.
- Frontend build still emits the pre-existing Sentry deprecation/configuration warnings and the `bigint` optional binding warning, but the production build completes successfully.
- `fwber-frontend/tsconfig.tsbuildinfo` changes during frontend validation because it is tracked in git.
- A first overlapping frontend build failed with `pages-manifest.json` missing in `.next`; a clean second build in a separate shell completed successfully, so the failure appears to have been `.next` build-directory contention rather than an app regression.

## Next Recommended Slice
1. Resume the deferred merchant follow-up by adding merchant pulse broadcast history and lifecycle tooling in the merchant portal.
2. Audit remaining bounty surfaces for any lingering dependence on the legacy `/matchmaker/bounty` contract beyond the intentional public show route.
3. Triage the browser/PWA warning noise separately from product bugs; the concrete bounty flow regressions are now addressed.
