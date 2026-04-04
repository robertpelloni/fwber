# PROJECT_STATUS.md - fwber v1.4.4 (Merchant Trust Scoring & Moderation)

**Date:** 2026-04-04
**Version:** 1.4.4 "Merchant Trust Scoring & Moderation"
**Status:** ✅ **VERIFIED, COMMITTED, AND READY FOR HETZNER CUTOVER**

---

## 🎯 What This Release Delivered
This release completes the next planned product-side follow-up after geo-aware merchant ranking: **merchant verification / trust scoring**.

Delivered:
- compact merchant trust scoring service
- merchant verification review endpoints for moderators/admins
- moderation dashboard support for reviewing merchant storefronts
- trust-aware nearby marketplace ranking that considers both distance and merchant trust
- trust score/tier surfaced through merchant API responses and storefront UI
- moderator compatibility fix via `User::is_moderator` accessor

## 🏪 Backend Improvements
- added `MerchantTrustService`
- added `MerchantModerationController`
- `MerchantProfile` now supports `verification_notes`, `verified_by`, and `verified_at`
- moderation routes restored for dashboard, flagged content, spoof detections, throttles, actions, users, and merchants
- moderation dashboard stats now include `pending_merchants`
- nearby marketplace ranking now uses a blended score from merchant trust + proximity instead of proximity alone

## 🌐 Frontend Improvements
- moderation dashboard now has a **Merchants** review tab with pending/verified/rejected filters and verify/reject/reset actions
- merchant dashboard/profile/storefront views now expose trust score and verification context
- moderation API layer now supports merchant review queue and merchant verification mutations
- moderation API base URL handling was hardened so it works whether `NEXT_PUBLIC_API_URL` includes `/api` or not

## ✅ Validation
- Backend tests passed:
  - `php artisan test tests/Feature/MerchantTrustModerationTest.php tests/Feature/MerchantRestoreTest.php tests/Feature/PremiumRestoreTest.php tests/Feature/AiWingmanRestoreTest.php tests/Feature/CoreDatingFlowTest.php tests/Feature/OptimizeCoreIndexesMigrationTest.php`
  - Result: **31 passed**
- Frontend production build passed:
  - `npm run build --prefix fwber-frontend`

## ✅ Why This Matters
Merchant discovery is now ranked by more than proximity alone. The restored commerce stack now has a real moderation and trust signal, which is much closer to production viability for public rollout.
