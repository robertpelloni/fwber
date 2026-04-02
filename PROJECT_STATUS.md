# Project Status — fwber v1.0.74 (ActivityPub Signed Outbound Delivery)

**Date:** 2026-04-02  
**Version:** 1.0.74 "ActivityPub Signed Outbound Delivery"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## ActivityPub Signed Outbound Delivery
- **Local actors now have real federation keypairs**: the backend generates and stores a dedicated encrypted RSA keypair for ActivityPub use, separate from the existing E2E encryption key path.
- **Actor documents now expose the real public key**: `/api/federation/users/{id}` no longer returns the mock `MockPublicKeyForIteration0347` placeholder and instead publishes the generated key that remote servers can verify against.
- **Outbound federation is now real instead of mocked**: `ActivityPubService::dispatchToRemoteInbox()` now resolves the remote actor's inbox, signs the outbound request with the local actor's private key, and performs the POST for Follow requests and follower broadcasts.
- **Federation auth is now two-sided**: inbound inbox traffic is already protected by signature verification from `v1.0.73`, and outbound follow delivery now signs requests with the same HTTP Signature contract.
- **The shared key table no longer blocks federation**: `user_public_keys` now supports multiple key types per user plus optional private-key storage, while the older E2E endpoints stay pinned to `ECDH` records.

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
- **Full federation backend coverage is green**: `php artisan test tests\Feature\ActivityPubTest.php tests\Feature\ActivityPubSignatureTest.php tests\Feature\ActivityPubOutboundTest.php tests\Feature\E2EKeyManagementTest.php` passes after the outbound signing work and the shared-key-table migration.
- **ActivityPub backend coverage is green**: `php artisan test tests\Feature\ActivityPubTest.php tests\Feature\ActivityPubSignatureTest.php` passes with the new inbound signature middleware and signed-request helpers.
- **Endpoint-specific backend coverage is green**: `php artisan test tests\Feature\LocationControllerTest.php tests\Feature\PhotoControllerTest.php tests\Feature\SafetyControllerTest.php` passes with the new DreamHost-hardening assertions.
- **Billing validation from the previous slice remains the current premium reference path**: `php artisan test tests\Feature\PremiumControllerTest.php tests\Feature\StripeWebhookTest.php`, plus frontend `npm run lint`, `npm run type-check`, and `cmd /c "npm run build"`, already passed for `v1.0.71`.

## ✅ Release Focus
- [x] Generate and persist real ActivityPub actor keypairs.
- [x] Replace mocked outbound federation delivery with signed HTTP POSTs.
- [x] Expose the generated actor public key in the local actor JSON-LD payload.
- [x] Preserve the existing E2E key APIs while sharing the underlying key table.
- [x] Add regression coverage for actor key exposure and signed follow delivery.
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
