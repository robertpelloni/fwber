# Project Status — fwber v1.0.97 (Merkle Prover & Audit UI)

**Date:** 2026-04-02  
**Version:** 1.0.97 "Merkle Prover & Audit UI"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Cryptographic Vote Verification
- **Frontend Prover:** Launched the `VoteVerifier.tsx` component. Users can now perform a local SHA-256 reconstruction of the Merkle path to verify their vote inclusion against the proposal's published root.
- **Audit Interface:** Redesigned the `/council` portal with a dedicated "History & Audit" section, treating governance outcomes as permanent, verifiable records.
- **Proof-as-a-Service:** The backend now provides a optimized proof-generation endpoint, reconstructing the Merkle tree on-the-fly to serve sibling hashes.

## Automated Policy & Rule Updates
- **Closed-Loop Governance:** Proposals of type `policy` now automatically execute their associated changes upon passing.
- **Dynamic Site Settings:** Implemented the `site_settings` table, enabling the community to vote on and change project constants (e.g. `daily_token_bonus`) without code deployments.

## Council Proposal Creation
- **Frontend Submission:** Users can now launch new community proposals directly from the `/council` dashboard via the `CreateProposalModal`.

## Full Federated E2E Security
- **Asymmetric Decryption:** Completed the security loop for cross-server DMs. Users now generate and persist RSA keypairs in the browser, enabling native decryption of incoming ActivityPub messages.
- **RSA-OAEP Bridge:** The frontend now automatically imports remote actor public keys and performs high-performance hybrid encryption for outbound federated messaging.

## Governance Execution & Policy
- **Automated Reconciler:** Launched the `ProcessGovernanceProposals` background job that finalizes expired community proposals. 
- **Proven Integrity:** Verified the weighted-vote summation logic via comprehensive feature testing in `GovernanceExecutionTest.php`.

## Global Token Bridge & Economy
- **Asset Bridging:** Users can now swap liquid FWB Tokens for external assets (SOL, USDC).
- **Price Simulation:** The swap interface now includes real-time simulated price feeds and 2% bridge fee calculations for transparent user expenditure.

## ✅ Release Focus
- [x] Build Frontend Merkle Prover UI.
- [x] Implement Merkle-tree vote verification.
- [x] Build real-time governance notifications.
- [x] Integrate real-time market price APIs.
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
