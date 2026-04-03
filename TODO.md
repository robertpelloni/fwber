# TODO — fwber Immediate Action Items

> **Version:** 1.0.77  
> **Last Updated:** 2026-04-02

---

## 🔴 Critical: Bug Fixes & Stability
- [ ] **Load Test EventStore**: Conduct load testing on the `EventStore` table across 10 million simulated records to check UUID constraint and composite index hit rates.
- [ ] **Mobile Expo Router Migration**: Prepare `mobile/` directory to align routing architecture with the web frontend.

## 🟡 High: Performance & Edge
- [x] **Multi-region Edge Caching**: Optimized Vercel `next.config.js` with immutable headers and documented Cloudflare edge caching strategies in `docs/ai/deployment/cloudflare-edge-caching.md`.
- [ ] **WASM Encryption Primitives**: Port client-side encryption logic to Rust-compiled WASM for higher performance.

## ✅ Recently Completed
- [x] **Multi-region Edge Caching**: Configured immutable static headers and documented Cloudflare Page Rule strategies.
- [x] **Helm Chart Infrastructure**: Created a standardized Helm chart for enterprise deployment of the full monorepo stack.
- [x] **Offline Sync CRDT Integration**: Upgraded the `useChatSync.ts` basic retry queue to a full CRDT batching system sending `last_sync_at` (logical clock) to the new `POST /messages/sync-batch` endpoint.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
