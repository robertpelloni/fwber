# ROADMAP.md — fwber Project Trajectory

> **Current Version:** 0.99.1
> **Last Updated:** 2026-03-25

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
- Offline-first IndexedDB CRDT synchronization.
- ActivityPub Federation and WebFinger discovery.

### Phase 5: Global Analysis & Intelligent Automation (ACTIVE)
- Comprehensive UI/UX coverage of all backend systems.
- Deep AI Wingman integration (Cosmic, Vibe, Nemesis, Roast).
- Gamification: Achievements, Leaderboards, Streaks, Token Economy.
- Analytics and Telemetry refinement.

### Phase 6: Production Hardening & Scale (UPCOMING)
- Rust `fwber-geo` microservice deployment for high-density geographic querying.
- Kubernetes / Helm chart finalization for enterprise scale.
- Multi-region edge caching (Cloudflare + Vercel).
- Load testing the unified Event Store.

---

## 🧩 Feature Matrix Status

| Feature Domain | Status | Backend | Frontend | Notes |
| :--- | :--- | :--- | :--- | :--- |
| **Auth & Security** | Mature | 100% | 100% | ZK-Identity, WebAuthn, Sanctum, OTP. |
| **Matching Engine** | Mature | 100% | 100% | Event Sourced, compatibility scoring. |
| **Messaging** | Mature | 100% | 95% | E2E Encryption, Offline Sync, Voice Memos. |
| **Location / Geo** | Mature | 100% | 90% | AR Nav, Safe Walk, Proximity Chatrooms. |
| **AI Integration** | Evolving | 100% | 85% | Avatars, Wingman, Roasts, Content Gen. |
| **Social / Viral** | Evolving | 100% | 80% | Rate My Cat, Bounties, Referrals. |
| **Economy** | Evolving | 100% | 80% | FWB Tokens, Stripe, Wallet, Leaderboard. |
| **Federation** | Beta | 90% | 20% | WebFinger works, Inbox/Outbox needs UI. |
| **B2B / Merchant** | Beta | 95% | 45% | Registration, promotions, analytics, vibe analysis, live pulse broadcasts, and broadcast history now work; deeper merchant lifecycle controls still need UI. |

---

## 🎯 Next Immediate Milestones
1. Extend the **Merchant Portal UI** (B2B) with lifecycle controls, resend/deactivate/reporting tooling, and broader operations polish.
2. Wire up the **ActivityPub Inbox/Outbox UI** for federated profiles.
3. Polish the **AI Wingman UI** (Proactive nudges, Cosmic Match visualization).
4. Deploy and integrate the **Rust Geo-Screener**.
