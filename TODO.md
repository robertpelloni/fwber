# TODO — fwber Immediate Action Items

> **Version:** 1.0.88  
> **Last Updated:** 2026-04-02

---

## 🔴 Critical: Global Scale
- [ ] **Kafka/Kinesis Integration**: Finalize the event streaming migration from Redis to a distributed broker.
- [ ] **Mobile Native NFC Bridge**: Implement a native module in `mobile/` to handle high-frequency NFC handshakes on iOS.

## 🟡 High: Feature Expansion
- [ ] **Federated Direct Messaging**: Implement secure E2E encrypted DMs between instances via the ActivityPub `Note` type with private addressing.

## ✅ Recently Completed
- [x] **Federated Feed Aggregator**: Merged remote social content with local match activity.
- [x] **AR Inventory Finder**: Spatial UI for local merchant marketplace items.
- [x] **ZK-Age Verification**: Authority-signed 18+ claims for federated actors.
- [x] **NFC Tap-to-Pay**: Built the Merchant POS and User payment handshake.
- [x] **Multi-region Edge Caching**: Configured immutable static headers and documented Cloudflare Page Rule strategies.
- [x] **Helm Chart Infrastructure**: Created a standardized Helm chart for enterprise deployment of the full monorepo stack.
- [x] **Offline Sync CRDT Integration**: Upgraded the `useChatSync.ts` basic retry queue to a full CRDT batching system sending `last_sync_at` (logical clock) to the new `POST /messages/sync-batch` endpoint.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
