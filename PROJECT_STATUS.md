# Project Status — fwber v1.0.39 (Bounty Flow Repair)

**Date:** 2026-04-02  
**Version:** 1.0.39 "Bounty Flow Repair"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Bounty Flow Repair
- **Rich Bounty Payloads**: `/api/bounties` now returns the nested `user.profile` and `user.photos` data the bounty cards actually consume, instead of only a thin user shell.
- **Stable Sorting Contract**: The live list endpoint now accepts the frontend's `sort=reward` alias and has focused regression coverage for the resulting highest-reward ordering.
- **Live Route Alignment**: Bounty creation and authenticated suggestions now target the current `/api/bounties` route family, while public shared bounty detail pages still use the dedicated public legacy GET route.
- **Dead Link Cleanup**: The bounties page now links back to `/dashboard` and opens the existing create-bounty modal instead of sending users to the broken `/home` and `/profile/bounty/create` routes.

## ✅ Release Focus
- [x] Repaired the `/api/bounties` payload contract so the list page can render live profile and photo data safely.
- [x] Added backend regression coverage for the bounty index route and the `sort=reward` alias.
- [x] Switched bounty creation and authenticated suggestions onto the live `/api/bounties` contract.
- [x] Replaced dead bounty navigation with working dashboard and modal flows.
- [x] Revalidated bounty backend coverage with `php artisan test tests/Feature/MatchBountyTest.php`.
- [x] Revalidated the frontend with `npm run type-check`.
- [x] Revalidated the frontend with `npm run lint`.
- [x] Revalidated the frontend with `npm run build`.
