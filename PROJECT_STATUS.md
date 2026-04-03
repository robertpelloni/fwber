# Project Status — fwber v1.1.5 (NFC Receipts & Kafka Sim)

**Date:** 2026-04-02  
**Version:** 1.1.5 "NFC Receipts & Kafka Sim"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## NFC Digital Transaction History
- **Verified Receipts:** Built the `DigitalReceipt.tsx` component. Users can now view, download, and share proof-of-purchase for any token-based venue transaction.
- **Unified Handshake UX:** The NFC hub now provides a seamless transition from "Tapping" to "Payment Confirmed" to "Receipt Issued" in a single animated flow.

## Simulated Kafka Event Infrastructure
- **Aggregate Partitioning:** Implemented a partition-aware event bus. The system now automatically shuffles domain events into 10 distinct logical partitions based on the `aggregate_uuid` hash, preparing the platform for a real Apache Kafka cluster.
- **Performance Logs:** Added high-speed append-only logs for each partition, ensuring sub-millisecond event publishing in the development environment.

## Community Court & Appeals
- **Judicial Layer:** Users restricted by the Council now have a formal path to reinstatement. The `/council/appeals` portal allows banned users to state their case.
- **Direct Democracy:** Appeals are instantly converted into community proposals, ensuring that the same FWB-weighted voting power that banned a user also decides their fate.

## On-Chain Governance Proofs
- **Solana Anchoring:** Finalized Merkle roots from community proposals are now anchored to the Solana blockchain via transaction memos.
- **Immutable History:** This provides a third-party verifiable trail of all governance outcomes.

## ✅ Release Focus
- [x] Build NFC Digital Receipt system.
- [x] Implement Simulated Kafka Partitioning.
- [x] Integrate Native NFC Mobile bridge.
- [x] Implement On-Chain Governance mirroring (Solana).
- [x] Schedule background governance reconcilers.
- [x] Build Council Appeal system and UI.
- [x] Implement automated Unban proposals.
- [x] Implement Community Moderation DAO actions.
- [x] Build Global Ban infrastructure and middleware.
- [x] Enable iOS native NFC entitlements.
- [x] Build Frontend Merkle Prover UI.
- [x] Implement Merkle-tree vote verification.
- [x] Build real-time governance notifications.
- [x] Integrate real-time market price APIs.
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
