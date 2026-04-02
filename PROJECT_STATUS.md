# Project Status — fwber v1.0.40 (Events and Shell Stabilization)

**Date:** 2026-04-02  
**Version:** 1.0.40 "Events and Shell Stabilization"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Events and Shell Stabilization
- **Nearby Events 500 Repair**: The event index now filters and sorts geospatial distance without relying on the alias-based `HAVING` pattern that was failing under production pagination.
- **Boost Timer Contract Fix**: The dashboard boost badge now unwraps the backend's real `/api/boosts/active` payload and renders a safe fallback instead of `NaN:NaN` when expiry data is missing or malformed.
- **Sidebar Shell Rollout**: Matches and Messages now run inside the shared protected app shell, which restores the sidebar and keeps page headers out from under the floating logo area.
- **Favicon Refresh + Copy Cleanup**: Browser favicon URLs are versioned to force refresh of the animated logo asset, and the roast/hype dialog copy no longer shows raw HTML entities.

## ✅ Release Focus
- [x] Repaired the production geolocated events query path and added a focused radius-filter regression test.
- [x] Fixed the boost active badge contract mismatch and hardened the countdown rendering.
- [x] Rolled the shared sidebar shell onto Matches and Messages.
- [x] Forced favicon refreshes for the animated logo asset and cleaned the roast/hype helper copy.
- [x] Revalidated backend coverage with `php artisan test tests/Feature/EventControllerTest.php tests/Feature/EventTypesTest.php tests/Feature/BoostControllerTest.php`.
- [x] Revalidated the frontend with `npm run type-check`.
- [x] Revalidated the frontend with `npm run lint`.
- [x] Revalidated the frontend with `npm run build`.
