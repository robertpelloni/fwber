# TODO — fwber Immediate Action Items

> **Version:** 1.0.91  
> **Last Updated:** 2026-04-02

---

## 🔴 Critical: Federated Security
- [ ] **Cross-Instance Decryption**: Implement RSA private key import for local users to decrypt incoming federated DMs (requires generating RSA pairs alongside ECDH).
- [ ] **Kafka/Kinesis Integration**: Finalize the event streaming migration from Redis to a distributed broker.

## 🟡 High: Governance Polish
- [ ] **On-Chain Proposal Verification**: Implement a ZK-proof to verify that a proposal's vote tally correctly matches the staked tokens on a public ledger.

## ✅ Recently Completed
- [x] **Cross-Instance E2E Encryption**: Added outbound RSA-OAEP support for federated messaging.
- [x] **Global Token Exchange**: Built the "Global Bridge" wallet UI and backend swap logic.
- [x] **Automated Proposal Execution**: Built the background job to finalize community votes.
- [x] **Governance Council Portal**: Built the token-weighted voting dashboard.
- [x] **Multi-region Edge Caching**: Configured immutable static headers and documented Cloudflare Page Rule strategies.
- [x] **Helm Chart Infrastructure**: Created a standardized Helm chart for enterprise deployment of the full monorepo stack.
- [x] **Offline Sync CRDT Integration**: Upgraded the `useChatSync.ts` basic retry queue to a full CRDT batching system sending `last_sync_at` (logical clock) to the new `POST /messages/sync-batch` endpoint.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
