# TODO — fwber Immediate Action Items

> **Version:** 1.0.81  
> **Last Updated:** 2026-04-02

---

## 🔴 Critical: Performance & Security
- [ ] **WASM Encryption Integration**: Compile the new `fwber-wasm` package and wire it into the frontend E2E hook to offload encryption to native Rust speed.
- [x] **Redis Bloom Filter Cache**: Implemented an "Active Cells" proxy in `GeoScreenerService.php` to reduce Rust microservice hits for cold geographic areas.

## 🟡 High: Feature Expansion
- [x] **ActivityPub Search Aggregator**: Updated `ActivityPubSearchController` to parallel-query multiple discovery hubs using `Http::pool`.

## ✅ Recently Completed
- [x] **ActivityPub Search Aggregator**: Enabled keyword-based federated search across multiple Mastodon/ActivityPub instances.
- [x] **Geo-Screener Bloom Filter**: Reduced spatial query latency via Redis-based activity caching.
- [x] **WASM Encryption Initiation**: Implemented AES-GCM-256 encryption primitives in Rust for browser-based E2E performance.
- [x] **Multi-region Edge Caching**: Configured immutable static headers and documented Cloudflare Page Rule strategies.
- [x] **Helm Chart Infrastructure**: Created a standardized Helm chart for enterprise deployment of the full monorepo stack.
- [x] **Offline Sync CRDT Integration**: Upgraded the `useChatSync.ts` basic retry queue to a full CRDT batching system sending `last_sync_at` (logical clock) to the new `POST /messages/sync-batch` endpoint.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
