# TODO — fwber Immediate Action Items

> **Version:** 1.0.97  
> **Last Updated:** 2026-04-02

---

## 🔴 Critical: Multi-Server Replication
- [ ] **Kafka/Kinesis Migration**: Finalize the event streaming migration from Redis to a distributed broker for global instance parity.

## 🟡 High: Immutability & Mobile
- [ ] **On-Chain Mirroring**: Implement a job to hash the daily Merkle roots and post them to the Solana blockchain for immutable proof of governance.
- [ ] **Native NFC Support**: Ensure the `mobile/` app exposes the Web NFC API correctly to the WebView, or implement a native `expo-nfc` bridge.

## ✅ Recently Completed
- [x] **Frontend Merkle Prover**: Users can now mathematically verify their votes in the Council UI.
- [x] **Merkle-based Verification**: Implemented cryptographic vote tallying for community proposals.
- [x] **Real-time Council Alerts**: Built WebSocket notifications for governance lifecycle events.
- [x] **Automated Rule Updates**: Built the `PolicyExecutor` to auto-apply community decisions to `site_settings`.
- [x] **Proposal Creation UI**: Launched the frontend submission modal for Council proposals.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
