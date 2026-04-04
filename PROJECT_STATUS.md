# PROJECT_STATUS.md - fwber v1.3.5 (Deployment Migration Idempotency)

**Date:** 2026-04-04
**Version:** 1.3.5 "Deployment Migration Idempotency"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## 🎯 What This Release Solved
This release directly addressed the deployment failure caused by the `2026_04_03_212041_optimize_core_indexes` migration crashing on MySQL with:

- `SQLSTATE[42000]: Duplicate key name 'idx_user_profiles_location'`

That failure pattern means the deployment target already had at least one of the intended indexes, likely from a partial prior migration attempt. Re-running the migration then exploded because the migration was written as a one-shot schema mutation instead of an idempotent deployment-safe step.

## 🛠️ Migration Hardening
- **Made the core index optimization migration idempotent:** each index is now created only if it does not already exist on the active connection.
- **Cross-driver index detection added:** the migration now checks existing indexes for:
  - MySQL / MariaDB
  - SQLite
  - PostgreSQL
- **Production deploy safety improved:** a partially applied migration can now be re-run without failing on duplicate-key errors.

## 🧰 Storage Access Hardening
- **Restricted-browser resilience:** introduced `fwber-frontend/lib/browser-storage.ts` and routed analytics session storage through safe wrappers so restricted contexts can fail gracefully instead of throwing noisy storage-access exceptions during page load.

## ✅ Validation
- **Regression test added:** `tests/Feature/OptimizeCoreIndexesMigrationTest.php`
- **Backend validation passed:**
  - `php artisan test tests/Feature/OptimizeCoreIndexesMigrationTest.php tests/Feature/NotificationSettingsAndAnalyticsTest.php tests/Feature/NotificationRoutingTest.php tests/Feature/BlockSafetyFlowTest.php tests/Feature/CoreDatingFlowTest.php`
  - Result: **26 passed**

## ✅ Release Focus
- [x] Rework `optimize_core_indexes` so retries are safe after partial deploy application.
- [x] Detect existing indexes before attempting to create them.
- [x] Add regression coverage that re-runs the migration successfully.
- [x] Re-verify the backend core suite slice after the migration hardening.
