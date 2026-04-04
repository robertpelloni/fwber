# PROJECT_STATUS.md - fwber v1.4.0 (Marketplace & Merchant Restoration)

**Date:** 2026-04-04
**Version:** 1.4.0 "Marketplace & Merchant Restoration"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND PUSHED**

---

## 🎯 What This Release Restored
This release completes the next planned restoration slice after AI and premium: the **merchant / marketplace surface**.

Restored in active runtime:
- merchant registration, profile, dashboard, inventory, analytics, and redemption backend endpoints
- marketplace storefront and purchase backend endpoints
- merchant schema (`merchant_profiles`, `merchant_inventories`, `merchant_payments`, `inventory_redemptions`)
- merchant models and user relations
- `/merchant/register`
- `/merchant/dashboard`
- `/merchant/inventory`
- `/merchant/profile`
- `/merchant/analytics`
- `/marketplace/[merchantId]`
- digital receipt UI and repaired AR inventory feed integration
- settings entry into the merchant portal

## 🏪 Backend Restoration
- **Controllers restored:**
  - `fwber-backend/app/Http/Controllers/MerchantController.php`
  - `fwber-backend/app/Http/Controllers/MerchantInventoryController.php`
  - `fwber-backend/app/Http/Controllers/MerchantAnalyticsController.php`
- **Models restored:**
  - `MerchantProfile`
  - `MerchantInventory`
  - `MerchantPayment`
  - `InventoryRedemption`
- **Schema restored:**
  - `2026_04_04_040000_restore_merchant_marketplace_tables.php`
- **Route surface restored:** merchant portal registration/profile/dashboard/inventory/analytics routes plus public marketplace browsing and authenticated purchase endpoints.
- **Payment integration strategy:** marketplace purchases intentionally use the same compact payment gateway pattern restored in v1.3.9 so mock mode remains usable locally while Stripe-backed purchases remain possible in production.

## 🌐 Frontend Restoration
- **New merchant pages:** register, dashboard, inventory, profile, analytics
- **New storefront page:** `app/marketplace/[merchantId]/page.tsx`
- **Restored commerce component:** `components/marketplace/DigitalReceipt.tsx`
- **Navigation restored:** merchant entry added back into settings and merchant header navigation rebuilt around active merchant routes instead of dead promotions pages.
- **AR repair:** `InventoryARView.tsx` now uses the restored nearby marketplace API instead of a hard-coded demo storefront.

## ✅ Validation
- **Backend tests passed:**
  - `php artisan test tests/Feature/MerchantRestoreTest.php tests/Feature/PremiumRestoreTest.php tests/Feature/AiWingmanRestoreTest.php tests/Feature/CoreDatingFlowTest.php tests/Feature/OptimizeCoreIndexesMigrationTest.php`
  - Result: **28 passed**
- **Frontend production build passed:**
  - `npm run build --prefix fwber-frontend`
  - Result: merchant and marketplace pages all appear in the production route map.

## ✅ Release Focus
- [x] Restore merchant/marketplace schema/models/controller surface.
- [x] Reintroduce merchant registration, inventory, redemption, analytics, and storefront routes.
- [x] Restore user-visible merchant portal pages and storefront UI.
- [x] Restore digital receipts and repair AR inventory browsing.
- [x] Re-verify backend tests and frontend production build.
