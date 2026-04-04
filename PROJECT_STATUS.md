# PROJECT_STATUS.md - fwber v1.3.4 (Console Error Sweep)

**Date:** 2026-04-04
**Version:** 1.3.4 "Console Error Sweep"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## 🎯 What This Release Solved
This release targeted real browser-console issues observed against production-style usage.

The reported problems broke down into four buckets:
1. stale homepage/share links were prefetching removed routes like `/roast` and `/rate-my-pussy`
2. frontend analytics was posting to `/api/analytics/events`, but the active backend route set did not expose that endpoint
3. notification preferences had a hidden frontend/backend route mismatch
4. login failures with malformed/non-JSON server responses were surfacing as raw JSON parse exceptions instead of actionable auth errors

## 🧹 Console/Error Hardening Delivered
- **Removed stale retired-route prefetches:** replaced homepage calls-to-action that were still pointing at archived routes, and updated share CTA fallback routing to use active pages.
- **Restored analytics ingestion endpoint:** registered `POST /api/analytics/events` publicly so the lightweight page-view client no longer generates 404 noise before login.
- **Fixed notification-preference route contract:** backend now exposes `PUT /api/notification-preferences/{type}`, which matches the existing frontend client behavior.
- **Hardened auth response parsing:** login/register/wallet/2FA flows now safely handle non-JSON error bodies and surface readable fallback messages instead of throwing `Unexpected non-whitespace character after JSON...` parser errors.

## ✅ Validation
- **Backend tests passed:**
  - `php artisan test tests/Feature/NotificationSettingsAndAnalyticsTest.php tests/Feature/NotificationRoutingTest.php tests/Feature/BlockSafetyFlowTest.php tests/Feature/CoreDatingFlowTest.php`
  - Result: **25 passed**
- **Frontend production build passed:**
  - `npm run build --prefix fwber-frontend`

## ✅ Release Focus
- [x] Eliminate stale-link 404 prefetches from active pages.
- [x] Restore the active analytics ingestion API route.
- [x] Repair notification settings route mismatch.
- [x] Make auth error parsing resilient to malformed/non-JSON responses.
- [x] Re-verify backend tests and frontend build.
