# Project Status — fwber v1.0.42 (Structured Match Interests)

**Date:** 2026-04-02  
**Version:** 1.0.42 "Structured Match Interests"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Structured Match Interests
- **Canonical Interests**: Profile interests are now normalized on write so matching can treat `Travel`, `travel`, and duplicate picks as one structured signal instead of noisy freeform variants.
- **Interest-Aware Match Feed**: `/api/matches` now accepts `interests[]` filters and returns `shared_interests` plus `shared_interest_count` for each candidate.
- **Shared-Interest Ranking**: The AI matching heuristic now boosts candidates with overlapping interests instead of treating interests as profile copy only.
- **Matches UI Upgrade**: The matches page now lets users filter by interests and shows shared-interest chips directly on the swipe card.

## ✅ Release Focus
- [x] Normalized profile interests into canonical lowercase tags on save so match filtering can rely on stable values.
- [x] Extended `/api/matches` with shared-interest filtering, overlap-aware scoring, and shared-interest response metadata.
- [x] Added backend regression coverage for shared-interest filtering on the match feed.
- [x] Added interest filter controls and shared-interest chip rendering to the matches page.
- [x] Tightened the `interest-social-graph` design docs so phase 1 matches the real codebase seam: existing profile interests first, taxonomy tables later.
- [x] Revalidated backend coverage with `php artisan test tests/Feature/MatchFilterTest.php tests/Unit/Services/AIMatchingServiceTest.php`.
- [x] Revalidated the frontend with `npm run type-check`.
- [x] Revalidated the frontend with `npm run lint`.
