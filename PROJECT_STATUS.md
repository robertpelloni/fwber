# Project Status — fwber v1.0.72 (Production 500 Endpoint Hardening)

**Date:** 2026-04-02  
**Version:** 1.0.72 "Production 500 Endpoint Hardening"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Production 500 Endpoint Hardening
- **Location writes now survive sidecar/event-store failure**: `/api/location` no longer returns a fatal 500 just because the event-sourcing append step fails; the user location projection still persists and the append failure is downgraded to logged telemetry.
- **Photo listing now tolerates broken legacy rows**: `/api/photos` no longer crashes when older or partially migrated photo records are missing `file_path` / `thumbnail_path`; the accessors now return empty URL strings instead of throwing.
- **Safety reads now degrade when optional tables are absent**: `/api/safety/walk/active` now returns `walk: null` rather than a database exception if DreamHost is missing the `safe_walks` table, and safety contacts follow the same empty-state fallback pattern.
- **The 500 triage note in `TODO.md` is now code-addressed**: the old instruction to add logging in `bootstrap/app.php` turned out to be stale because the exception handler already logs API failures; the meaningful fix was endpoint hardening around the actual brittle paths.
- **Regression coverage now matches the suspected DreamHost failure modes**: backend tests now prove location still succeeds if the event store fails, photo listing tolerates null paths, and safety active-walk lookup degrades cleanly on schema drift.

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
- **Endpoint-specific backend coverage is green**: `php artisan test tests\Feature\LocationControllerTest.php tests\Feature\PhotoControllerTest.php tests\Feature\SafetyControllerTest.php` passes with the new DreamHost-hardening assertions.
- **Billing validation from the previous slice remains the current premium reference path**: `php artisan test tests\Feature\PremiumControllerTest.php tests\Feature\StripeWebhookTest.php`, plus frontend `npm run lint`, `npm run type-check`, and `cmd /c "npm run build"`, already passed for `v1.0.71`.

## ✅ Release Focus
- [x] Keep `/api/location` from failing the whole request when event-store append work flakes.
- [x] Keep `/api/photos` from 500ing on legacy rows with missing storage paths.
- [x] Keep `/api/safety/walk/active` from 500ing when DreamHost is missing the safety tables.
- [x] Add regression coverage for the suspected production-only failure modes.
- [x] Document the findings and the actual root-cause shape in the release docs.
