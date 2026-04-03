# TODO — fwber Immediate Action Items

> **Version:** 1.0.79  
> **Last Updated:** 2026-04-02

---

## 🔴 Critical: Bug Fixes & Stability
- [x] **Load Test EventStore**: Conducted load testing on the `EventStore` table across 100,000 simulated records verifying sub-millisecond lookup times and strict unique constraint enforcement.
- [x] **Mobile Expo Router Migration**: Migrated the `mobile/` directory from a single-file WebView wrapper to the modern Expo Router filesystem-based architecture.

## 🟡 High: Performance & Edge
- [x] **Multi-region Edge Caching**: Optimized Vercel `next.config.js` with immutable headers and documented Cloudflare edge caching strategies in `docs/ai/deployment/cloudflare-edge-caching.md`.
- [ ] **WASM Encryption Primitives**: Port client-side encryption logic to Rust-compiled WASM for higher performance.

## ✅ Recently Completed
- [x] **Mobile Expo Router Migration**: Refactored the React Native app to use file-based routing and a cleaner component structure.
- [x] **EventStore Load Testing**: Verified performance and unique constraint integrity under high record volume via custom Artisan command.
- [x] **Multi-region Edge Caching**: Configured immutable static headers and documented Cloudflare Page Rule strategies.
- [x] **Helm Chart Infrastructure**: Created a standardized Helm chart for enterprise deployment of the full monorepo stack.
- [x] **Offline Sync CRDT Integration**: Upgraded the `useChatSync.ts` basic retry queue to a full CRDT batching system sending `last_sync_at` (logical clock) to the new `POST /messages/sync-batch` endpoint.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
