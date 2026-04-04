# PROJECT_STATUS.md - fwber v1.4.5 (Merchant Review Prioritization)

**Date:** 2026-04-04
**Version:** 1.4.5 "Merchant Review Prioritization"
**Status:** ✅ **VERIFIED, COMMITTED, AND READY FOR HETZNER CUTOVER**

---

## 🎯 What This Release Delivered
This release extends the newly restored merchant moderation system with queue prioritization and faster operator workflows.

Delivered:
- merchant moderation queue now computes `priority_score` and `priority_tier`
- moderation queue now supports search across merchant name, category, location, and address
- moderation dashboard merchant tab now includes search, priority badges, and inline review notes handling
- nearby marketplace ranking continues to use trust + proximity, while merchant review tooling now better surfaces which storefronts deserve attention first

## 🏪 Backend Improvements
- `MerchantModerationController` now computes merchant review priority and sorts queue results by priority score
- merchant review queue supports `search` filtering
- moderation dashboard remains guarded against missing moderation-support tables

## 🌐 Frontend Improvements
- moderation dashboard merchant tab now includes:
  - search field
  - priority score/tier badge
  - latest verification notes display
  - inline moderator note editing via blur-save behavior
- moderation hooks/api now support merchant queue search

## ✅ Validation
- Backend tests passed:
  - `php artisan test tests/Feature/MerchantTrustModerationTest.php tests/Feature/MerchantRestoreTest.php tests/Feature/PremiumRestoreTest.php tests/Feature/AiWingmanRestoreTest.php tests/Feature/CoreDatingFlowTest.php tests/Feature/OptimizeCoreIndexesMigrationTest.php`
  - Result: **31 passed**
- Frontend production build passed:
  - `npm run build --prefix fwber-frontend`

## ✅ Why This Matters
The merchant moderation queue is now more practical for real operations. Moderators can search, prioritize, and annotate merchant reviews much more effectively instead of working through a flat pending list.
