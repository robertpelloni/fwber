# TODO — fwber Immediate Action Items

> **Version:** 1.0.83  
> **Last Updated:** 2026-04-02

---

## 🔴 Critical: Security & Scale
- [ ] **Distributed Global Event Streaming**: Implement Kafka-based event replication between federated instances.
- [ ] **Native NFC Support**: Ensure the `mobile/` app exposes the Web NFC API correctly to the WebView, or implement a native `expo-nfc` bridge.

## 🟡 High: Feature Expansion
- [ ] **Federated Reputation System**: Build a system to aggregate "Vouch" scores across instances to create a global Trust Score for federated actors.

## ✅ Recently Completed
- [x] **ZK-Location Verification**: Built a Redis-backed geohash handshake for physical profile exchange.
- [x] **NFC Match Protocol**: Built instant physical profile exchange via Web NFC.
- [x] **WASM Encryption Integration**: Wired Rust-compiled primitives into the frontend E2E bridge.
- [x] **Multi-region Edge Caching**: Configured immutable static headers and documented Cloudflare Page Rule strategies.
- [x] **Helm Chart Infrastructure**: Created a standardized Helm chart for enterprise deployment of the full monorepo stack.
- [x] **Offline Sync CRDT Integration**: Upgraded the `useChatSync.ts` basic retry queue to a full CRDT batching system sending `last_sync_at` (logical clock) to the new `POST /messages/sync-batch` endpoint.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
