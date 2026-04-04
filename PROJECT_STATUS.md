# PROJECT_STATUS.md - fwber v1.4.3 (Geo-Aware Merchant Ranking)

**Date:** 2026-04-04
**Version:** 1.4.3 "Geo-Aware Merchant Ranking"
**Status:** ✅ **VERIFIED, COMMITTED, AND READY FOR HETZNER CUTOVER**

---

## 🎯 What This Release Delivered
This release completes the previously identified follow-up gap in the restored merchant stack: nearby marketplace and AR inventory overlays now use **real merchant coordinates** instead of demo-relative positioning.

Delivered:
- merchant profiles now persist `location_name`, `latitude`, and `longitude`
- merchant registration inherits location from the user profile when explicit merchant coordinates are not provided
- merchant profile editing now exposes geo-aware storefront location controls with current-location autofill
- marketplace nearby API now filters and sorts by real distance when user coordinates are supplied
- AR inventory view now consumes real nearby merchant coordinates from the API
- storefront and merchant dashboard now surface merchant location context in the UI

## 🏪 Backend Improvements
- `MerchantProfile` now stores and casts merchant coordinates
- `2026_04_04_040000_restore_merchant_marketplace_tables.php` now includes merchant location columns and index
- `MerchantController` now defaults merchant coordinates from the user profile when appropriate
- `MerchantInventoryController::nearby()` now computes real distance-based ordering and returns `distance_m`, `lat`, and `lng`

## 🌐 Frontend Improvements
- `/merchant/register` now lets merchants save location name + coordinates and offers a geolocation autofill button
- `/merchant/profile` now supports editing geo-aware storefront location data
- `/merchant/dashboard` now displays current merchant location context
- `/marketplace/[merchantId]` now surfaces merchant location and coordinates
- `InventoryARView.tsx` now renders true nearby merchant inventory overlays using returned coordinates

## ✅ Validation
- Backend tests passed:
  - `php artisan test tests/Feature/MerchantRestoreTest.php tests/Feature/PremiumRestoreTest.php tests/Feature/AiWingmanRestoreTest.php tests/Feature/CoreDatingFlowTest.php tests/Feature/OptimizeCoreIndexesMigrationTest.php`
  - Result: **29 passed**
- Frontend production build passed:
  - `npm run build --prefix fwber-frontend`

## ✅ Why This Matters
The merchant/marketplace restore is now materially more complete. Nearby merchant discovery is no longer just structurally restored; it is now spatially meaningful enough to support real-world ranking and better AR presentation.
