# Project Status — fwber v1.0.73 (ActivityPub Inbox Signature Verification)

**Date:** 2026-04-02  
**Version:** 1.0.73 "ActivityPub Inbox Signature Verification"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## ActivityPub Inbox Signature Verification
- **Unsigned federation payloads are now rejected at the route boundary**: `/api/federation/users/{id}/inbox` now requires a valid HTTP signature before the inbox controller will touch the activity body.
- **Inbound signature checks now cover the important replay and tamper surfaces**: the new verification path enforces `Signature`, `Date`, and `Digest` headers, rejects stale requests, and verifies the signature against the remote actor's published RSA public key.
- **Payload actors must match the signing key owner**: the middleware now rejects requests where the signed key belongs to one actor but the JSON activity claims to be from another.
- **Existing inbox flows are still intact once authenticated**: Follow, Undo, and Accept handling continue to work after the route guard, and the old inbox tests now send real signed requests instead of anonymous JSON.
- **The next federation gap is narrower now**: outbound ActivityPub delivery is still mocked in `ActivityPubService`, so signed outbound posts/follows and local actor key generation are the remaining high-value federation slice.

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
- **ActivityPub backend coverage is green**: `php artisan test tests\Feature\ActivityPubTest.php tests\Feature\ActivityPubSignatureTest.php` passes with the new inbound signature middleware and signed-request helpers.
- **Endpoint-specific backend coverage is green**: `php artisan test tests\Feature\LocationControllerTest.php tests\Feature\PhotoControllerTest.php tests\Feature\SafetyControllerTest.php` passes with the new DreamHost-hardening assertions.
- **Billing validation from the previous slice remains the current premium reference path**: `php artisan test tests\Feature\PremiumControllerTest.php tests\Feature\StripeWebhookTest.php`, plus frontend `npm run lint`, `npm run type-check`, and `cmd /c "npm run build"`, already passed for `v1.0.71`.

## ✅ Release Focus
- [x] Require valid HTTP signatures on inbound ActivityPub inbox requests.
- [x] Reject stale/tampered federation requests before controller processing.
- [x] Keep the established Follow / Undo / Accept inbox flows working with signed requests.
- [x] Add regression coverage for valid and invalid signature paths.
- [x] Document outbound signed delivery as the next federation implementation gap.
- [x] Keep `/api/location` from failing the whole request when event-store append work flakes.
- [x] Keep `/api/photos` from 500ing on legacy rows with missing storage paths.
- [x] Keep `/api/safety/walk/active` from 500ing when DreamHost is missing the safety tables.
- [x] Add regression coverage for the suspected production-only failure modes.
- [x] Document the findings and the actual root-cause shape in the release docs.
