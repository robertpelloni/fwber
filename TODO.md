# TODO — fwber Immediate Action Items

> **Version:** 1.1.2  
> **Last Updated:** 2026-04-02

---

## 🔴 Critical: Global Parity
- [ ] **Cross-Server Event Relay**: Build a background worker that consumes events from the `KafkaEventBus` (or Redis) and replays them on other federated instances to sync state.
- [ ] **NFC "Tap-to-Pay" Mobile Verification**: Test the new Native NFC bridge in a physical environment to verify token transfers.

## 🟡 High: UI/UX Refinement
- [ ] **Federated Profile Completion**: Guide shadow users to complete their local profile attributes (e.g. mbti, looking_for) after their first federated login.

## ✅ Recently Completed
- [x] **Pluggable Event Bus**: Refactored `EventStore` to support Kafka, Redis, and Log drivers.
- [x] **Federated Login UI**: Built the React interface for ActivityPub challenge-response auth.
- [x] **Global Federated Identity**: Enabled decentralized login via ActivityPub handles.
- [x] **Native Mobile NFC Bridge**: Integrated `react-native-nfc-manager` for reliable hardware access.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
