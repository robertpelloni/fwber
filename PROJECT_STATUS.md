# PROJECT_STATUS.md - fwber v1.4.5 (Merchant Review Prioritization)

**Date:** 2026-04-04
**Version:** 1.4.5 "Merchant Review Prioritization"
**Status:** ✅ **VERIFIED, COMMITTED, AND READY FOR HETZNER CUTOVER**

---

## 🎯 What This Release Delivered
This release extends the restored merchant moderation system with practical review-ops improvements so moderators can process storefronts more effectively.

Delivered:
- merchant moderation queue priority scoring and priority tiers
- merchant moderation queue search across business name, category, location, and address
- merchant moderation dashboard improvements for faster review triage
- inline verification-note editing workflow in the moderation UI

## 🏪 Backend Improvements
- `MerchantModerationController` now computes `priority_score` and `priority_tier`
- merchant moderation queue supports `search` filtering
- queue results are sorted by priority score instead of only raw recency

## 🌐 Frontend Improvements
- moderation dashboard merchant tab now includes:
  - search box
  - priority badge
  - verification notes display
  - inline note editing behavior
- moderation hooks and API clients now support queue search

## ✅ Validation
- Backend tests passed:
  - `php artisan test tests/Feature/MerchantTrustModerationTest.php tests/Feature/MerchantRestoreTest.php tests/Feature/PremiumRestoreTest.php tests/Feature/AiWingmanRestoreTest.php tests/Feature/CoreDatingFlowTest.php tests/Feature/OptimizeCoreIndexesMigrationTest.php`
  - Result: **31 passed**
- Frontend production build passed:
  - `npm run build --prefix fwber-frontend`

## ✅ Why This Matters
Merchant review is no longer just technically present; it is now operationally usable. Moderators can triage likely-high-value or urgent storefronts first and quickly locate merchants in the queue.
