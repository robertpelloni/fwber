# Handoff — Merchant Broadcast History

**Date:** 2026-04-02  
**Status:** ✅ Local release verified  
**Version:** 1.0.41

## Overview
This cycle extended the merchant portal analytics surface with a proper broadcast history feed. Instead of creating a separate history endpoint, the existing `/api/merchant-portal/analytics` contract now includes recent Local Pulse merchant broadcasts, and the merchant analytics UI renders them in a compact audit panel.

## Accomplishments
- Extended `MerchantAnalyticsService` with a `getBroadcastHistory()` aggregation that filters recent `announce` artifacts down to the merchant's own `merchant_pulse_broadcast` sends.
- Returned the new `broadcasts` payload from `MerchantAnalyticsController@index` so the merchant portal keeps a single analytics fetch contract.
- Added focused `MerchantAnalyticsTest` coverage for both service-level history aggregation and the analytics endpoint response shape.
- Added a broadcast history panel to `app/merchant/analytics/page.tsx` showing content, promotion, vibe target, live vibe snapshot, promo code, radius, send time, and expiry status.
- Suppressed `GlobalSubpageNav` on `/dashboard` and loosened the shared `AppHeader` logo/status spacing so the floating-logo region no longer collides with the dashboard top bar.

## Key Files Modified
- `fwber-backend/app/Services/MerchantAnalyticsService.php`
  - Added merchant broadcast history aggregation using `merchant_pulse_broadcast` proximity artifact metadata.
- `fwber-backend/app/Http/Controllers/MerchantAnalyticsController.php`
  - Added the new `broadcasts` payload to the merchant analytics response.
- `fwber-backend/tests/Feature/MerchantAnalyticsTest.php`
  - Added regression coverage for merchant broadcast history service aggregation and endpoint output.
- `fwber-frontend/app/merchant/analytics/page.tsx`
  - Added a read-only broadcast history panel to the merchant analytics UI.
- `fwber-frontend/lib/hooks/use-merchant-analytics.ts`
  - Extended the typed analytics response with the new broadcast history records.
- `fwber-frontend/components/AppHeader.tsx`
  - Increased the header height slightly and shifted the connection badge farther right from the logo cluster.
- `fwber-frontend/components/GlobalSubpageNav.tsx`
  - Disabled the floating fallback nav on the dashboard so it cannot overlap the local header there.
- `VERSION`
- `package.json`
- `fwber-frontend/package.json`
- `CHANGELOG.md`
- `PROJECT_STATUS.md`
- `VERSION.md`
- `TODO.md`
- `ROADMAP.md`

## Validation
- `php artisan test tests/Feature/MerchantAnalyticsTest.php tests/Feature/MerchantPulseTest.php`
- `npm run lint`
- `npm run type-check`
- `npm run build`

## Notes / Risks
- Frontend lint still reports the pre-existing `react-hooks/exhaustive-deps` warning in `fwber-frontend/lib/api/photos.ts:476`.
- `fwber-frontend/tsconfig.tsbuildinfo` changes during frontend validation because it is tracked in git.

## Next Recommended Slice
1. Follow the merchant history slice with lifecycle controls such as resend/deactivate/reporting hooks if those are still desired.
2. Start the requested larger feature program with requirements/design/planning docs for the structured interest graph and related social-discovery surfaces.
3. Triage the remaining browser/PWA/Sentry warning noise separately from the concrete product regressions fixed in recent cycles.
