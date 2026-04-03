# TODO — fwber Immediate Action Items

> **Version:** 1.0.93  
> **Last Updated:** 2026-04-02

---

## 🔴 Critical: Global Economy & Sync
- [ ] **Global Token Swap UI Polish**: Add real-time price feeds for SOL/USDC to the `SwapInterface`.
- [ ] **Kafka/Kinesis Integration**: Finalize the event streaming migration from Redis to a distributed broker.

## 🟡 High: Governance Polish
- [ ] **On-Chain Proposal Verification**: Implement a ZK-proof to verify that a proposal's vote tally correctly matches the staked tokens on a public ledger.
- [ ] **Automated Rule Updates**: Build the event listeners for `Passed` proposals to auto-update system settings.

## ✅ Recently Completed
- [x] **Cross-Instance E2E Decryption**: Enabled local RSA key generation and message decryption for federated DMs.
- [x] **Automated Proposal Execution**: Built and tested the background job for finalising community votes.
- [x] **Global Token Exchange**: Built the "Global Bridge" wallet UI and backend swap logic.
- [x] **Governance Council Portal**: Built the token-weighted voting dashboard.
- [x] **Federated Secure DMs**: Enabled private person-to-person messaging via ActivityPub.
- [x] **Federated Feed Aggregator**: Merged remote social content with local match activity.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
