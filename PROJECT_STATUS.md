# Project Status — fwber v1.0.71 (Plan-Aware Premium Pricing)

**Date:** 2026-04-02  
**Version:** 1.0.71 "Plan-Aware Premium Pricing"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Plan-Aware Premium Pricing
- **The backend now owns premium plan metadata**: `config/premium.php` plus `PremiumPlanCatalog` define the current `gold_monthly` plan with configurable Stripe USD price, duration, token cost, and Stripe price key, replacing the old ignored `plan_id` flow.
- **Unknown premium plans now fail honestly**: `/api/premium/initiate` and `/api/premium/purchase` now reject unsupported `plan_id` values with `422` instead of silently falling back to the hardcoded monthly settings.
- **Stripe, token, and webhook fulfillment now stay in sync**: the configured plan metadata now drives Stripe intent creation, direct charge metadata, token purchase duration, `stripe_price` persistence, and webhook-driven premium expiration timing.
- **The premium modal no longer lies about a hardcoded card price**: `PremiumUpgradeModal` now treats card checkout as a backend-driven step and uses the backend-returned payment-intent amount inside the Stripe form instead of baking in `$19.99/mo` text ahead of time.
- **Plan coverage is regression-tested**: the billing feature suite now proves configured-plan initiation, configured-plan Stripe purchases, invalid plan rejection, renewal referral payouts, and webhook plan metadata handling all behave as expected.

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
- **Backend billing coverage is green**: `php artisan test tests\Feature\PremiumControllerTest.php tests\Feature\StripeWebhookTest.php` passes with the new plan-aware pricing assertions included.
- **Frontend lint is still effectively clean for this slice**: `npm run lint` reports only the long-standing `fwber-frontend/lib/api/photos.ts:476` `react-hooks/exhaustive-deps` warning.
- **Frontend type-check is green**: `npm run type-check` passes after the modal typing changes.
- **Frontend build is green through the reliable Windows path**: `cmd /c "npm run build"` succeeds in the clean worktree even though direct overlapping PowerShell builds can still be artifact-sensitive.

## ✅ Release Focus
- [x] Create a backend-owned premium plan catalog for the live `gold_monthly` offer.
- [x] Make premium initiation/purchase reject unknown `plan_id` values instead of ignoring them.
- [x] Use configured plan metadata across Stripe intent creation, direct charges, token purchases, and webhook fulfillment.
- [x] Remove the remaining hardcoded card-price assumption from the premium modal.
- [x] Reconfirm backend/frontend validation for the plan-aware pricing slice.
