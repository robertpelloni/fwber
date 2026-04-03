# TODO — fwber Immediate Action Items

> **Version:** 1.0.89  
> **Last Updated:** 2026-04-02

---

## 🔴 Critical: Federated Polish
- [ ] **E2E for Federated DMs**: Map remote actor public keys from the Actor profile to the `useE2EEncryption` hook to enable true server-to-server encryption.
- [ ] **Kafka/Kinesis Integration**: Finalize the event streaming migration from Redis to a distributed broker.

## 🟡 High: Feature Expansion
- [ ] **ZK-Age Verification**: Build the UI frontend for issuer-signed age proofs.

## ✅ Recently Completed
- [x] **Federated Secure DMs**: Enabled private person-to-person messaging via ActivityPub.
- [x] **Federated Feed Aggregator**: Merged remote social content with local match activity.
- [x] **AR Inventory Finder**: Spatial UI for local merchant marketplace items.
- [x] **Multi-region Edge Caching**: Configured immutable static headers and documented Cloudflare Page Rule strategies.
- [x] **Helm Chart Infrastructure**: Created a standardized Helm chart for enterprise deployment of the full monorepo stack.
- [x] **Offline Sync CRDT Integration**: Upgraded the `useChatSync.ts` basic retry queue to a full CRDT batching system sending `last_sync_at` (logical clock) to the new `POST /messages/sync-batch` endpoint.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
