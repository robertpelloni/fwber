# Project Status — fwber v1.0.76 (Offline CRDT Batch Sync)

**Date:** 2026-04-02  
**Version:** 1.0.76 "Offline CRDT Batch Sync"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Offline CRDT Batch Sync & Logical Clocks
- **Batch Sync Controller:** Created `MessageController::syncBatch` which consumes arrays of offline messages, verifies UUID idempotency, records historical dates, inserts the `MessageSent` events at their proper `created_at` timestamp, and updates the local Match. 
- **Logical Timestamp Tracking:** The `fwber-frontend` now maintains a `fwber_last_chat_sync` logical timestamp. `useChatSync.ts` queries the backend since the last sync time and receives any server-side messages the user missed while offline.
- **WebSocket Reconnection Injection:** Built `injectMissedMessages` inside `usePusherLogic.ts` that safely sorts incoming batch updates against live chat arrays without duplicating UUIDs.

## AI Wingman Chat Dashboard & Hardware UI
- **AI Wingman tools are directly accessible in chat:** Replaced the plain ice-breaker suggestions with a full `WingmanDashboardModal` inside `RealTimeChat.tsx`. Users can now directly request an honest compatibility audit, a nemesis profile, or an astrological fortune read from the match context.
- **Visualized the `compatibilityAudit` structure:** The AI response breaks down overall compatibility, strengths, weaknesses, and a fun boolean for surviving the apocalypse, complete with Framer Motion animations.
- **Hardware Token Ping Visualization:** Modified the BLE hardware token settings panel to include an active, simulated ping button, vibrating the device and providing visual confirmation for physical proximity testing.
- **Massive Autonomous Instruction Rewrite:** `docs/UNIVERSAL_LLM_INSTRUCTIONS.md` and all agent profiles (Claude, Gemini, GPT, Copilot) have been rewritten with an extreme set of execution loops.

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
- [x] Create `POST /api/messages/sync-batch`.
- [x] Deduplicate offline messages by UUID.
- [x] Fetch missed server messages since `last_sync_at`.
- [x] Inject missed offline messages via `use-chat-sync.ts`.
- [x] Integrate `compatibilityAudit` into the chat UI.
- [x] Integrate `findNemesis` into the chat UI.
- [x] Integrate `predictFortune` into the chat UI.
- [x] Implement `WingmanDashboardModal`.
- [x] Upgrade Hardware Token UI to support visual pings.
- [x] Fully document extreme continuous autonomous looping parameters for AI agents.
- [x] Generate real ActivityPub actor keypairs.
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
