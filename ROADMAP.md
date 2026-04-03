# ROADMAP.md — fwber Project Trajectory

> **Current Version:** 1.0.90
> **Last Updated:** 2026-04-02

---

## 🗺️ High-Level Trajectory

### Phase 1: Foundation & Monolith (COMPLETED)
- Basic matching, chat, and profile systems.
- Laravel + Next.js integration.
- Database architecture and initial API.

### Phase 2: The "Anti-Catfish" Guarantee (COMPLETED)
- AI Avatar generation (DALL-E 3).
- ZK-Identity Verification Protocol.
- AWS Rekognition for content moderation.
- Face Reveal mechanics and Relationship Tiers.

### Phase 3: The "Local Pulse" & Safety (COMPLETED)
- Proximity artifacts and local feeds.
- Safe Walk, Emergency Contacts, Panic Button.
- AR "Ghost" Navigation for finding matches in crowds.
- Hardware Token API for BLE physical interactions.

### Phase 4: Decentralization & Real-time (COMPLETED)
- Event Sourcing migration for core domains (Location, Messages, Matches).
- Laravel Reverb WebSocket integration for instant messaging.
- Offline-first IndexedDB CRDT synchronization via batch sync logic.
- ActivityPub Federation with full outbound signature handling and local keypairs.

### Phase 5: Global Analysis & Intelligent Automation (COMPLETED)
- Comprehensive UI/UX coverage of all backend systems.
- Deep AI Wingman integration (Cosmic, Vibe, Nemesis, Roast) directly in chat interfaces.
- Gamification: Achievements, Leaderboards, Streaks, Token Economy.
- Merchant/B2B Portal: Dashboard, Deactivation/Resend logic, CSV Exports, Live Vibe detection.
- Unified scene discovery across all match algorithms.

### Phase 6: Production Hardening & Scale (COMPLETED)
- Rust `fwber-geo` microservice deployment for high-density geographic querying.
- Kubernetes / Helm chart finalization for enterprise scale.
- Multi-region edge caching (Cloudflare + Vercel).
- Load testing the unified Event Store.
- Prepare **Mobile Expo Router** migration for the `mobile/` directory.

### Phase 7: Physical & Federated Expansion (COMPLETED)
- Develop a **Sub-millisecond Real-time Proximity Cache**.
- Finalize **ActivityPub Search Aggregator**.
- Implement **WASM Encryption Primitives**.
- Build a **NFC Match Protocol** with **ZK-Location Verification**.
- Develop **Distributed Global Event Streaming**.
- Build a **Physical Item Marketplace**.
- Implement **Federated Reputation Aggregator**.
- Implement **ActivityPub Group Actors**.
- Build a **NFC "Tap-to-Pay"** protocol.
- Build a **Federated Feed Aggregator**.
- Implement **ZK-Age Verification**.
- Build an **AR Inventory Finder**.
- Implement **Federated Secure DMs**.

### Phase 8: Decentralized Governance (ACTIVE)
- Build a **Governance & Voting** portal (Council) (COMPLETED).
- Implement **Token-Weighted Voting Logic** (COMPLETED).
- Implement **Cross-Instance E2E Encryption** using remote actor public keys.
- Develop a **Global Token Exchange** to allow swapping FWB Tokens for other assets.

---

## 🧩 Feature Matrix Status

| Feature Domain | Status | Backend | Frontend | Notes |
| :--- | :--- | :--- | :--- | :--- |
| **Auth & Security** | Mature | 100% | 100% | ZK-Identity, ZK-Location, NFC Sync, WASM. |
| **Matching Engine** | Mature | 100% | 100% | Event Sourced, compatibility scoring. |
| **Messaging** | Mature | 100% | 100% | E2E Encrypted, CRDT Sync, Federated DMs. |
| **Location / Geo** | Mature | 100% | 100% | AR Nav, Redis Bloom, ZK-Proofs, AR Finder. |
| **AI Integration** | Mature | 100% | 100% | Avatars, Wingman modals, Roast, Content Gen. |
| **Social / Viral** | Mature | 100% | 100% | Rate My Cat, Bounties, Federated Social. |
| **Economy** | Mature | 100% | 100% | FWB Tokens, Stripe, Marketplace, NFC Pay. |
| **Federation** | Mature | 100% | 100% | Search, Reputation, Groups, Secure DMs. |
| **Governance** | Mature | 100% | 100% | Council Portal, Weighted Voting, Proposals. |
| **DevOps / Infra** | Mature | 100% | 100% | Helm Chart, Edge Caching, Event Streaming. |
| **Mobile App** | Mature | 100% | 100% | Expo Router, WebView Hybrid, NFC Support. |

---

## 🎯 Next Immediate Milestones
1. Implement **Cross-Instance E2E Encryption** using remote actor public keys.
2. Develop a **Global Token Exchange** to allow swapping FWB Tokens for other assets.
3. Build **Automated Proposal Execution** jobs for community policy enforcement.
