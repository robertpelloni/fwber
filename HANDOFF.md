# Handoff — Events and Shell Stabilization

**Date:** 2026-04-02  
**Status:** ✅ Local release verified  
**Version:** 1.0.40

## Overview
This cycle stabilized several live regressions after the bounty repair. The patch fixed the production nearby-events geospatial query path, corrected the frontend contract mismatch behind the broken boost countdown, rolled the shared app shell onto Matches and Messages, and forced a favicon refresh so browsers pick up the animated logo asset again.

## Accomplishments
- Replaced the nearby-events `HAVING distance` geospatial pagination pattern in `EventController@index` with a safer raw distance filter/order flow that behaves cleanly under production pagination.
- Added focused `EventControllerTest` coverage proving the nearby-events radius filter excludes distant events.
- Fixed the frontend boost contract mismatch by unwrapping the real `{ data: boost | null }` response from `/api/boosts/active`, then hardened the countdown UI against invalid expiry timestamps so it no longer renders `NaN:NaN`.
- Rolled the shared `AppHeader` shell onto Matches and Messages so both pages show the sidebar and keep header content clear of the floating logo.
- Added versioned favicon/icon URLs in the root layout to force browser refreshes of the animated logo asset, and cleaned `RoastGenerator` copy so it no longer renders raw HTML entities.

## Key Files Modified
- `fwber-backend/app/Http/Controllers/EventController.php`
  - Reworked the geospatial filter to avoid alias-based `HAVING` pagination behavior and sort nearby results safely by computed distance.
- `fwber-backend/tests/Feature/EventControllerTest.php`
  - Added regression coverage for the nearby-events radius filter path.
- `fwber-frontend/lib/hooks/use-boosts.ts`
  - Aligned the active boost hook with the backend's real response shape.
- `fwber-frontend/lib/api/boosts.ts`
  - Normalized the standalone boost helpers to the same active-boost payload contract.
- `fwber-frontend/components/BoostButton.tsx`
  - Added safe expiry parsing and cleaner countdown formatting/fallback behavior.
- `fwber-frontend/components/viral/RoastGenerator.tsx`
  - Removed raw HTML entity strings from the helper and result copy.
- `fwber-frontend/app/matches/page.tsx`
  - Wrapped Matches in the shared protected app shell.
- `fwber-frontend/app/messages/page.tsx`
  - Wrapped Messages in the shared protected app shell and moved the page header block below the shared header.
- `fwber-frontend/app/layout.tsx`
  - Added versioned favicon/icon URLs to force browser cache refreshes of the animated logo asset.
- `VERSION`
- `package.json`
- `fwber-frontend/package.json`
- `CHANGELOG.md`
- `PROJECT_STATUS.md`
- `VERSION.md`
- `TODO.md`
- `ROADMAP.md`

## Validation
- `php artisan test tests/Feature/EventControllerTest.php tests/Feature/EventTypesTest.php tests/Feature/BoostControllerTest.php`
- `npm run lint`
- `npm run type-check`
- `npm run build`

## Notes / Risks
- Frontend lint still reports the pre-existing `react-hooks/exhaustive-deps` warning in `fwber-frontend/lib/api/photos.ts:476`.
- Frontend build still emits the pre-existing Sentry deprecation/configuration warnings and the `bigint` optional binding warning, but the production build completes successfully.
- `fwber-frontend/tsconfig.tsbuildinfo` changes during frontend validation because it is tracked in git.
- Avoid overlapping frontend builds in the same checkout; one earlier overlapping run failed on a missing `.next` manifest even though clean reruns succeeded.

## Next Recommended Slice
1. Resume the deferred merchant follow-up by adding merchant pulse broadcast history and lifecycle tooling in the merchant portal.
2. Start the requested larger feature program with requirements/design/planning docs for the structured interest graph and related social-discovery surfaces.
3. Triage the remaining browser/PWA/Sentry warning noise separately from the concrete product regressions fixed in this cycle.
