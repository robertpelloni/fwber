# TODO — fwber Immediate Action Items

> **Version:** 1.0.87  
> **Last Updated:** 2026-04-02

---

## 🔴 Critical: Federated Social
- [ ] **Federated Feed Aggregator**: Build a background worker to ingest and merge posts from followed remote actors into the main user feed.
- [ ] **Kafka/Kinesis Integration**: Finalize the event streaming migration from Redis to a distributed broker.

## 🟡 High: Feature Expansion
- [ ] **ZK-Age Verification**: Implement a zero-knowledge protocol to verify 18+ status via ActivityPub profiles.

## ✅ Recently Completed
- [x] **NFC Tap-to-Pay**: Built the Merchant POS and User payment handshake for physical item purchases.
- [x] **Federated Reputation Aggregator**: Periodically syncs trust scores from remote instances.
- [x] **ActivityPub Group Actors**: Communities are now federated actors.
- [x] **ZK-Location Verification**: Built a Redis-backed geohash handshake for physical profile exchange.
- [x] **Multi-region Edge Caching**: Configured immutable static headers and documented Cloudflare Page Rule strategies.
- [x] **Helm Chart Infrastructure**: Created a standardized Helm chart for enterprise deployment of the full monorepo stack.
- [x] **Offline Sync CRDT Integration**: Upgraded the `useChatSync.ts` basic retry queue to a full CRDT batching system sending `last_sync_at` (logical clock) to the new `POST /messages/sync-batch` endpoint.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
