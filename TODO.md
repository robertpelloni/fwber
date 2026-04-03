# TODO — fwber Immediate Action Items

> **Version:** 1.0.80  
> **Last Updated:** 2026-04-02

---

## 🔴 Critical: Performance & Security
- [ ] **WASM Encryption Integration**: Compile the new `fwber-wasm` package and wire it into the frontend E2E hook to offload encryption to native Rust speed.
- [ ] **Redis Bloom Filter Cache**: Implement a Bloom filter layer in `GeoScreenerService.php` to reduce Rust microservice hits for cold geographic areas.

## 🟡 High: Feature Expansion
- [ ] **ActivityPub Search Aggregator**: Build a background job to aggregate Actor results from multiple configured instances during global search.

## ✅ Recently Completed
- [x] **WASM Encryption Initiation**: Implemented AES-GCM-256 encryption primitives in Rust for browser-based E2E performance.
- [x] **Mobile Expo Router Migration**: Refactored the React Native app to use file-based routing and a cleaner component structure.
- [x] **EventStore Load Testing**: Verified performance and unique constraint integrity under high record volume via custom Artisan command.
- [x] **Multi-region Edge Caching**: Configured immutable static headers and documented Cloudflare Page Rule strategies.
- [x] **Helm Chart Infrastructure**: Created a standardized Helm chart for enterprise deployment of the full monorepo stack.
- [x] **Offline Sync CRDT Integration**: Upgraded the `useChatSync.ts` basic retry queue to a full CRDT batching system sending `last_sync_at` (logical clock) to the new `POST /messages/sync-batch` endpoint.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
