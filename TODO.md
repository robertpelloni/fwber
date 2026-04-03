# TODO — fwber Immediate Action Items

> **Version:** 1.0.84  
> **Last Updated:** 2026-04-02

---

## 🔴 Critical: Scale & UI
- [ ] **Marketplace UI**: Build the "Shop" interface in the frontend to browse merchant inventory and purchase items.
- [ ] **Native NFC Support**: Ensure the `mobile/` app exposes the Web NFC API correctly to the WebView, or implement a native `expo-nfc` bridge.

## 🟡 High: Feature Expansion
- [ ] **ActivityPub Group Actors**: Implement "Group" type actors to allow federated bulletin boards.
- [ ] **Federated Reputation System**: Finalize the aggregation job to sync Vouch scores across instances.

## ✅ Recently Completed
- [x] **Distributed Global Event Streaming**: Implemented Redis Stream driver for real-time domain event replication.
- [x] **Physical Item Marketplace**: Built backend infrastructure for token-based inventory and redemptions.
- [x] **ZK-Location Verification**: Built a Redis-backed geohash handshake for physical profile exchange.
- [x] **Multi-region Edge Caching**: Configured immutable static headers and documented Cloudflare Page Rule strategies.
- [x] **Helm Chart Infrastructure**: Created a standardized Helm chart for enterprise deployment of the full monorepo stack.
- [x] **Offline Sync CRDT Integration**: Upgraded the `useChatSync.ts` basic retry queue to a full CRDT batching system sending `last_sync_at` (logical clock) to the new `POST /messages/sync-batch` endpoint.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
