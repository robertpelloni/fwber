# Project Status — fwber v1.0.70 (Stripe Renewal Rollout Follow-Up)

**Date:** 2026-04-02  
**Version:** 1.0.70 "Stripe Renewal Rollout Follow-Up"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Stripe Renewal Rollout Follow-Up
- **Stripe success redirects now land somewhere real**: the frontend now serves `/premium/success`, giving redirect-based Stripe confirmations a dedicated success state and then moving users into subscription settings instead of dropping them on a missing route.
- **Renewal MLM payouts now match first-time premium upgrades**: `invoice.payment_succeeded` webhook handling now passes successful Stripe renewals through `ReferralCommissionService`, so both direct uplines and second-level uplines receive the same configured cash/FWBcoin rewards on recurring premium revenue.
- **Backend env examples now match the live billing code**: `.env.example` now documents `PAYMENT_DRIVER=stripe` plus the level-1 and level-2 referral commission env knobs already consumed by `config/referrals.php`, reducing rollout ambiguity for production Stripe setup.
- **Regression coverage now includes the renewal commission path**: the Stripe webhook feature suite proves a renewal invoice records the payment and awards both referral levels instead of silently skipping recurring MLM rewards.
- **Windows frontend validation is still artifact-sensitive but source-clean**: backend billing tests pass, frontend type-check passes, lint still shows only the long-standing `lib/api/photos.ts:476` hook warning, and the app build completes successfully when run via `cmd /c "npm run build"` in this shared Windows workspace.

## Premium Billing Hardening
- **Unsafe Gold grant removed**: `/api/premium/purchase` no longer falls back to the mock `tok_visa` token. Stripe upgrades now require either a real `payment_method_id` or a confirmed `payment_intent_id`.
- **Visible upgrade surfaces are back on the safe path**: both `/premium` and `/settings/subscription` now open the existing `PremiumUpgradeModal`, which routes card payments through Stripe Elements and keeps the explicit 200 FWB upgrade option.
- **Webhook verification matches the live config shape**: the Stripe webhook controller now reads `services.stripe.webhook.secret` first and still honors the older flat key as a compatibility fallback.
- **Referral loop copy is now user-visible on the homepage**: the landing page now briefly explains that direct and second-level premium upgrades can eventually earn small cash/FWBcoin rewards once billing is fully configured.
- **Subscription history dollars display correctly**: the standalone subscription history page no longer divides already-stored currency amounts by 100 a second time.

## Structured Interest Graph Bridge
- **Profile Interests Now Resolve Into Topics**: Profile updates canonicalize interest values against the existing topic taxonomy, map aliases onto stable topic slugs, preserve unmatched freeform interests, and automatically sync matched topics into `followedTopics()` without disturbing prior follows.
- **Profile API Now Exposes Structured Interest Topics**: Authenticated and public profile responses include `interest_topics` with `match_source` metadata, making it clear which interest hubs come from profile text, explicit follows, or both.
- **Live Profile Editor Uses Topic-Backed Chips**: The `/profile` page now loads topic suggestions from the live topics API, lets users toggle them directly, and merges those selections with the older hobby/music/movie/book/sport buckets when saving.
- **Match Filters Use the Shared Topic Taxonomy**: The shared-interest filter chips in matching now pull from the same topic catalog rather than a duplicated hardcoded list, keeping profile editing and discovery aligned on the same interest graph.
- **Validation Path Is Cleaner on Windows**: `tsconfig.json` now excludes stale renamed dependency folders so `tsc` no longer walks backup `node_modules` directories, and the old stale folder from the earlier repair pass has been removed.

## Current Validation / Delivery State
- **Backend billing coverage is green**: `php artisan test tests\Feature\PremiumControllerTest.php tests\Feature\StripeWebhookTest.php` passes, including the new renewal-referral commission assertion path.
- **Frontend lint is still effectively clean for this slice**: `npm run lint` reports only the long-standing `fwber-frontend/lib/api/photos.ts:476` `react-hooks/exhaustive-deps` warning.
- **Frontend type-check is green**: a fresh `npm run type-check` passes after the Stripe success page addition.
- **Frontend build is green through the reliable Windows path**: `cmd /c "npm run build"` succeeds, while direct overlapping PowerShell builds can still surface transient missing-manifest errors in this shared worktree.

## ✅ Release Focus
- [x] Document the missing Stripe production env and referral commission knobs in backend example config.
- [x] Add a real `/premium/success` landing page for redirect-based Stripe upgrades.
- [x] Award two-level referral commissions on successful Stripe renewal invoices.
- [x] Add Stripe webhook regression coverage for the renewal MLM payout path.
- [x] Reconfirm backend/frontend validation for the billing follow-up slice.
