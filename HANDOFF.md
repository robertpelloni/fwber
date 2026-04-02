# Handoff — Merchant Pulse Broadcast Activation

**Date:** 2026-04-02  
**Status:** ✅ Local release verified  
**Version:** 1.0.38

## Overview
This cycle completed the next merchant-portal slice by replacing the fake merchant pulse broadcast queue with a real broadcast path. Merchant broadcasts now reuse proximity announce artifacts, spend tokens only on successful sends, and reject vibe-target mismatches honestly without pretending work was queued anywhere.

## Accomplishments
- Activated `POST /api/merchant/pulse/broadcast` with a real send path in `MerchantPulseController`.
- Reused the merchant's latest mapped promotion as the owned broadcast location source.
- Charged 50 FWB Tokens only when a broadcast actually sends.
- Added live vibe-target gating for `any`, `energetic`, `chill`, and `romantic`.
- Returned explicit `blocked_vibe_mismatch` and missing-location failures so the frontend can guide merchants clearly.
- Updated the merchant vibe deck UI copy and toast handling to reflect real send-or-block behavior instead of a fake queued state.
- Added regression coverage for successful send, mismatch rejection, and missing-location failure, while preserving the earlier vibe-location tests.

## Key Files Modified
- `fwber-backend/app/Http/Controllers/MerchantPulseController.php`
  - Added real artifact creation, token spending, vibe gating, and explicit response metadata.
- `fwber-backend/tests/Feature/MerchantPulseTest.php`
  - Added focused broadcast tests and disabled the daily-login bonus middleware in this suite so token assertions stay isolated to merchant pulse behavior.
- `fwber-frontend/app/merchant/vibe/page.tsx`
  - Updated success/failure messaging, surfaced the live detected vibe, and aligned the deck copy with the real backend contract.
- `VERSION`
- `package.json`
- `fwber-frontend/package.json`
- `CHANGELOG.md`
- `PROJECT_STATUS.md`
- `VERSION.md`
- `TODO.md`
- `ROADMAP.md`

## Validation
- `php artisan test tests/Feature/MerchantPulseTest.php tests/Feature/MerchantPromotionCrudTest.php tests/Feature/MerchantAnalyticsTest.php`
- `npm run lint`
- `npm run type-check`
- `npm run build`

## Notes / Risks
- Frontend lint still reports the pre-existing `react-hooks/exhaustive-deps` warning in `fwber-frontend/lib/api/photos.ts:476`.
- Frontend build still emits the pre-existing Sentry deprecation/configuration warnings and the `bigint` optional binding warning, but the production build completes successfully.
- `fwber-frontend/tsconfig.tsbuildinfo` changes during frontend validation because it is tracked in git.

## Next Recommended Slice
1. Add merchant pulse broadcast history in the merchant portal so sent broadcasts can be reviewed after launch.
2. Expose artifact/reporting or deactivate controls for merchant broadcasts if the announce stream needs moderation or lifecycle management from the portal.
3. Consider whether vibe-targeted broadcasts need a real deferred/queued scheduler later; right now the system is intentionally honest and immediate-only.
