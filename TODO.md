# TODO — fwber Immediate Action Items

> **Version:** 1.0.82  
> **Last Updated:** 2026-04-02

---

## 🔴 Critical: Security & Scale
- [ ] **Distributed Global Event Streaming**: Implement Kafka-based event replication between federated instances.
- [ ] **ZK-Location Verification**: Add zero-knowledge proofs to the `NFCProfileExchange` to verify location without revealing exact GPS coordinates during the tap.

## 🟡 High: Feature Expansion
- [ ] **ActivityPub Group Actors**: Implement "Group" type actors to allow federated bulletin boards.

## ✅ Recently Completed
- [x] **NFC Match Protocol**: Built instant physical profile exchange via Web NFC.
- [x] **WASM Encryption Integration**: Wired Rust-compiled primitives into the frontend E2E bridge.
- [x] **ActivityPub Search Aggregator**: Enabled keyword-based federated search across multiple Mastodon/ActivityPub instances.
- [x] **Multi-region Edge Caching**: Configured immutable static headers and documented Cloudflare Page Rule strategies.
- [x] **Helm Chart Infrastructure**: Created a standardized Helm chart for enterprise deployment of the full monorepo stack.
- [x] **Offline Sync CRDT Integration**: Upgraded the `useChatSync.ts` basic retry queue to a full CRDT batching system sending `last_sync_at` (logical clock) to the new `POST /messages/sync-batch` endpoint.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
