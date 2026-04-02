# Handoff — Structured Match Interests

**Date:** 2026-04-02  
**Status:** ✅ Local release verified  
**Version:** 1.0.42

## Overview
This cycle started the requested `interest-social-graph` program with the smallest durable production slice: reuse the existing `UserProfile.interests` seam, normalize interests on save, make `/api/matches` filter and rank by shared interests, and surface shared-interest context directly in the swipe UI. The umbrella feature docs were also tightened so phase 1 now reflects the real codebase architecture instead of describing a second profile-interest persistence layer up front.

## Accomplishments
- Added canonical interest normalization in `UserProfile` so saved interests are deduplicated, trimmed, and lowercased consistently.
- Extended `MatchFilterRequest`, `MatchController`, `AIMatchingService`, and `MatchResource` so `/api/matches` accepts `interests[]`, boosts shared-interest overlap, and returns `shared_interests` metadata.
- Added focused `MatchFilterTest` coverage proving shared-interest filtering and response metadata.
- Added shared-interest filter chips to `components/MatchFilter.tsx` and shared-interest chip rendering to `app/matches/page.tsx`.
- Updated the `docs/ai/feature-interest-social-graph` design/planning/implementation/testing docs so phase 1 is grounded in the existing profile/matching stack and now includes a mermaid architecture diagram.

## Key Files Modified
- `fwber-backend/app/Models/UserProfile.php`
  - Normalizes the persisted `interests` array on write.
- `fwber-backend/app/Http/Requests/MatchFilterRequest.php`
  - Added validation for `interests[]` match filters.
- `fwber-backend/app/Services/AIMatchingService.php`
  - Added shared-interest normalization, overlap scoring, and candidate filtering helpers.
- `fwber-backend/app/Http/Controllers/MatchController.php`
  - Passes interest filters into the matching service and decorates match candidates with shared-interest metadata.
- `fwber-backend/app/Http/Resources/MatchResource.php`
  - Exposes `shared_interests` and `shared_interest_count` to the frontend.
- `fwber-backend/tests/Feature/MatchFilterTest.php`
  - Added regression coverage for shared-interest feed filtering.
- `fwber-frontend/components/MatchFilter.tsx`
  - Added interest filter controls to the existing matches filter panel.
- `fwber-frontend/app/matches/page.tsx`
  - Renders shared-interest chips and counts on the swipe card.
- `docs/ai/design/feature-interest-social-graph.md`
- `docs/ai/planning/feature-interest-social-graph.md`
- `docs/ai/implementation/feature-interest-social-graph.md`
- `docs/ai/testing/feature-interest-social-graph.md`
  - Tightened phase-1 architecture around the current codebase seam and added the missing mermaid diagram.
- `VERSION`
- `package.json`
- `fwber-frontend/package.json`
- `CHANGELOG.md`
- `PROJECT_STATUS.md`
- `VERSION.md`
- `TODO.md`
- `ROADMAP.md`

## Validation
- `php artisan test tests/Feature/MatchFilterTest.php tests/Unit/Services/AIMatchingServiceTest.php`
- `npm run lint`
- `npm run type-check`
- `npm run build`

## Notes / Risks
- Frontend lint still reports the pre-existing `react-hooks/exhaustive-deps` warning in `fwber-frontend/lib/api/photos.ts:476`.
- `fwber-frontend/tsconfig.tsbuildinfo` changes during frontend validation because it is tracked in git.
- The clean phase-1 slice still uses `UserProfile.interests` as the source of truth; the later curated taxonomy tables and visibility primitives remain future work.

## Next Recommended Slice
1. Add a lightweight curated interest catalog API so the profile and match filter UIs no longer rely on duplicated hardcoded interest lists.
2. Follow immediately with friends-only / circle-only visibility primitives before journals, hubs, and relationship links start using them.
3. Triage the remaining browser/PWA/Sentry warning noise separately from the concrete product slices already shipped.
