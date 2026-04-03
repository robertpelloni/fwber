# TODO — fwber Immediate Action Items

> **Version:** 1.0.86  
> **Last Updated:** 2026-04-02

---

## 🔴 Critical: Multi-Instance Scale
- [ ] **Kafka/Kinesis Integration**: Finalize the event streaming migration from Redis to a distributed broker.
- [ ] **Federated Content Moderation**: Implement shared report signals for ActivityPub actors.

## 🟡 High: Feature Expansion
- [ ] **NFC "Tap-to-Pay"**: Integrate the `Marketplace` with NFC profile exchange to allow "Tapping to Pay" at merchant venues.

## ✅ Recently Completed
- [x] **Federated Reputation Aggregator**: Periodically syncs trust scores from remote instances.
- [x] **ActivityPub Group Actors**: Communities are now federated actors.
- [x] **Mobile NFC Support**: Enabled hardware permissions for Android.
- [x] **ZK-Location Verification**: Built a Redis-backed geohash handshake for physical profile exchange.
- [x] **Multi-region Edge Caching**: Configured immutable static headers and documented Cloudflare Page Rule strategies.
- [x] **Helm Chart Infrastructure**: Created a standardized Helm chart for enterprise deployment of the full monorepo stack.
- [x] **Offline Sync CRDT Integration**: Upgraded the `useChatSync.ts` basic retry queue to a full CRDT batching system sending `last_sync_at` (logical clock) to the new `POST /messages/sync-batch` endpoint.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
