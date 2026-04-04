# TODO — fwber Immediate Action Items

> **Version:** 1.3.5  
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Go-to-Market
- [ ] **Redeploy After Migration Fix**: Re-run deployment now that `optimize_core_indexes` is idempotent and verify migrations complete cleanly.
- [ ] **TestFlight Verification**: Confirm the GitHub Actions / EAS pipeline successfully deposits the `.ipa` into App Store Connect.
- [ ] **Play Console Verification**: Confirm the mobile release workflow successfully lands the `.aab` in Google Play internal testing.

## 🟡 High: Product Polish
- [ ] **Store Asset Production**: Execute the screenshot and copy plan in `mobile/STORE_ASSETS.md`.
- [ ] **Real-Device Notification QA**: Verify foreground, background, and cold-start notification flows on physical iOS/Android devices now that routes and toasts are standardized.
- [ ] **Production Login 500 Root Cause Audit**: Inspect live backend logs for the production `/api/auth/login` 500 so the server-side failure itself is fixed, not just the frontend error handling.

## ✅ Recently Completed
- [x] **Deployment Migration Idempotency**: `2026_04_03_212041_optimize_core_indexes.php` now skips indexes that already exist, preventing duplicate-key failures on redeploy.
- [x] **Optimize Indexes Regression Test**: Added `tests/Feature/OptimizeCoreIndexesMigrationTest.php` to prove the migration can be re-run safely.
- [x] **Console Error Sweep**: Removed stale retired-route prefetches, restored analytics event ingestion routing, and hardened auth parsing against malformed server responses.
- [x] **Notification Settings Contract Fix**: Backend now exposes `PUT /api/notification-preferences/{type}` to match the frontend API client.
- [x] **Sentry Build Modernization**: Replaced deprecated client/instrumentation patterns and removed Sentry-specific frontend build warnings.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
