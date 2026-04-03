# TODO — fwber Immediate Action Items

> **Version:** 1.0.94  
> **Last Updated:** 2026-04-02

---

## 🔴 Critical: Global Economy & Sync
- [ ] **Real-time Price API**: Replace simulated price feeds in the `SwapInterface` with real data from the Jupiter or CoinGecko API.
- [ ] **Kafka/Kinesis Integration**: Finalize the event streaming migration from Redis to a distributed broker.

## 🟡 High: Governance Expansion
- [ ] **On-Chain Proposal Verification**: Implement a ZK-proof to verify that a proposal's vote tally correctly matches the staked tokens on a public ledger.

## ✅ Recently Completed
- [x] **Automated Rule Updates**: Built the `PolicyExecutor` to auto-apply community decisions to `site_settings`.
- [x] **Proposal Creation UI**: Launched the frontend submission modal for Council proposals.
- [x] **Cross-Instance E2E Decryption**: Enabled local RSA key generation and message decryption for federated DMs.
- [x] **Automated Proposal Execution**: Built and tested the background job for finalising community votes.
- [x] **Global Token Exchange**: Built the "Global Bridge" wallet UI and backend swap logic.
- [x] **Governance Council Portal**: Built the token-weighted voting dashboard.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
