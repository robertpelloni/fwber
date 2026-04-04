# PROJECT_STATUS.md - fwber v1.3.6 (Migration Column-Guard Hardening)

**Date:** 2026-04-04
**Version:** 1.3.6 "Migration Column-Guard Hardening"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## 🎯 What This Release Solved
After the first deployment-safe retry fix, deployment exposed a second real-world schema drift issue:

- `SQLSTATE[42000]: Key column 'order' doesn't exist in table`
- failing while adding `idx_photos_user_order`

That means the deployment target's `photos` table does not fully match the current squashed local schema. Even though the migration had become idempotent with respect to duplicate indexes, it still assumed every indexed column existed.

## 🛠️ Migration Guard Improvements
- **Column-aware index creation added:** the migration now skips an index if any required column is missing.
- **Retry safety expanded beyond duplicate keys:** the deploy no longer fails just because one environment is missing a column expected by a performance-only index.
- **Photos table drift handled gracefully:** `idx_photos_user_order` is skipped when the `order` column is absent instead of crashing deployment.

## ✅ Validation
- **Expanded regression test:** `OptimizeCoreIndexesMigrationTest` now also simulates a `photos` table missing the `order` column.
- **Backend validation passed:**
  - `php artisan test tests/Feature/OptimizeCoreIndexesMigrationTest.php tests/Feature/NotificationSettingsAndAnalyticsTest.php tests/Feature/NotificationRoutingTest.php tests/Feature/BlockSafetyFlowTest.php tests/Feature/CoreDatingFlowTest.php`
  - Result: **27 passed**
- **Frontend build remained green:**
  - `npm run build --prefix fwber-frontend`

## ✅ Release Focus
- [x] Skip index creation when the referenced columns do not exist.
- [x] Extend migration regression coverage for missing-column deploy drift.
- [x] Re-verify backend tests after the guard expansion.
- [x] Keep frontend build verified after the backend-only fix.
