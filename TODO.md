# TODO — fwber Immediate Action Items

> **Version:** 1.0.96  
> **Last Updated:** 2026-04-02

---

## 🔴 Critical: Multi-Server Replication
- [ ] **Kafka/Kinesis Migration**: Finalize the event streaming migration from Redis to a distributed broker for global instance parity.

## 🟡 High: Transparency & Verification
- [ ] **Frontend Merkle Prover**: Add a "Verify My Vote" button in the Council UI that uses the `merkle_root` to prove a user's vote was included in the tally.
- [ ] **On-Chain Mirroring**: Implement a job to hash the daily Merkle roots and post them to the Solana blockchain for immutable proof of governance.

## ✅ Recently Completed
- [x] **Merkle-based Verification**: Implemented cryptographic vote tallying for community proposals.
- [x] **Real-time Council Alerts**: Built WebSocket notifications for governance lifecycle events.
- [x] **Automated Rule Updates**: Built the `PolicyExecutor` to auto-apply community decisions to `site_settings`.
- [x] **Proposal Creation UI**: Launched the frontend submission modal for Council proposals.
- [x] **Cross-Instance E2E Decryption**: Enabled local RSA key generation and message decryption for federated DMs.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
