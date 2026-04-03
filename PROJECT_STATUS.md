# Project Status — fwber v1.1.4 (Merkle Prover & Event Consumer)

**Date:** 2026-04-02  
**Version:** 1.1.4 "Merkle Prover & Event Consumer"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## On-Chain Verification
- **Double-Verified Roots:** The `VoteVerifier` now cross-checks the local Merkle path against the Solana ledger. This ensures that the Council's reported results are identical to the immutable on-chain record.
- **Chain of Trust:** Visualized the verification sequence from user vote -> Merkle leaf -> local root -> Solana memo.

## Distributed State Synchronization
- **Event Consumer:** Launched the `ConsumeFederatedEvents` worker. Instances can now "listen" to the global event bus and sync profiles, trust scores, and matches across the mesh.
- **Reliable Relay:** Implemented consumer group logic to ensure events are processed exactly once per node.

## Native NFC Verification
- **E2E Proven:** Verified the native mobile NFC bridge via Cypress. Automated tests now confirm that tapping a merchant device triggers the correct payment and token deduction flow.

## Global Federated Identity
- **Decentralized Login:** Users can now use their ActivityPub handle (e.g. Mastodon) to log in to the fwber network. 
- **Shadow User Sync:** Implemented automated local user creation for remote actors.

## ✅ Release Focus
- [x] Integrate On-Chain Merkle Prover.
- [x] Build Federated Event Consumer.
- [x] Run NFC Tap-to-Pay E2E tests.
- [x] Build Global Federated Identity system.
- [x] Build Native Mobile NFC Bridge.
- [x] Build Council Appeal system and UI.
- [x] Implement automated Unban proposals.
- [x] Implement Community Moderation DAO actions.
- [x] Build Global Ban infrastructure and middleware.
- [x] Implement On-Chain Governance mirroring (Solana).
- [x] Enable iOS native NFC entitlements.
- [x] Build Frontend Merkle Prover UI.
- [x] Implement Merkle-tree vote verification.
- [x] Build real-time governance notifications.
- [x] Integrate real-time market price APIs.
- [x] Schedule background governance reconcilers.
- [x] Build Automated Rule Updates (Policy Executor).
- [x] Create Proposal Creation UI in frontend.
- [x] Polish Global Token Swap UI with price feeds.
- [x] Implement Full Cross-Instance E2E Encryption/Decryption.
- [x] Build Automated Proposal Execution jobs.
- [x] Build Governance & Voting portal.
- [x] Implement token-weighted voting logic.
- [x] Build Global Token Exchange (Bridge) UI.
- [x] Implement ZK-Age Verification.
- [x] Build NFC "Tap-to-Pay" protocol for merchants.
- [x] Enable Mobile NFC hardware permissions.
- [x] Build Marketplace UI for users to spend tokens.
- [x] Build Physical Item Marketplace for merchants.
- [x] Implement Distributed Global Event Streaming (Redis).
- [x] Build ZK-Location Verification for physical taps.
- [x] Implement NFC Profile Exchange protocol.
- [x] Integrate WASM crypto offloading in frontend.
- [x] Aggregate federated search results in parallel.
- [x] Implement Redis Bloom Filter for geo-caching.
- [x] Migrate Mobile app to Expo Router.
- [x] Standardize Mobile directory structure.
- [x] Load test EventStore under high record volume.
- [x] Verify event unique constraint enforcement.
- [x] Optimize Next.js Cache-Control headers for production.
- [x] Document Cloudflare global edge caching strategy.
- [x] Create standardized Helm Chart for enterprise deployment.
- [x] Template all monorepo components (PHP, Next.js, Rust).
- [x] Configure production-ready default values.
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
