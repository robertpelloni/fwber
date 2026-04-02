# Project Status — fwber v1.0.67 (Premium Billing Hardening)

**Date:** 2026-04-02  
**Version:** 1.0.67 "Premium Billing Hardening"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

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
- **Backend interest-graph coverage is green**: `ProfileUpdateTest` and `SceneDiscoveryFeatureTest` pass with the new canonicalization, topic-sync, and `interest_topics` response assertions.
- **Frontend lint is effectively clean for this slice**: a fresh lint run reports only the long-standing `fwber-frontend/lib/api/photos.ts:476` `react-hooks/exhaustive-deps` warning; the new `/profile` interest-graph work does not add warnings.
- **Frontend build and type-check are now confirmed green in a fresh subprocess**: the earlier `.next` manifest/trace failures were transient artifact races in overlapping Windows worktree builds, not source regressions in the released code.

## ✅ Release Focus
- [x] Rename all shipped legacy token references to FWBcoin.
- [x] Fix the frontend type issues uncovered during the structured interest-graph follow-up validation pass.
- [x] Confirm fresh frontend lint/build/type-check status after the interest-graph follow-up.
- [x] Bridge freeform profile interests into the structured topic graph without replacing the existing topic/follow system.
- [x] Expose structured profile interest topics and source metadata through the profile API.
- [x] Update the live profile and match-filter UI to use topic-backed interest chips.
- [x] Add backend regression coverage for profile-interest canonicalization and scene-summary structured interest exposure.
- [x] Eliminate stale `node_modules` backup folders as a TypeScript validation hazard in the fresh repair worktree.
