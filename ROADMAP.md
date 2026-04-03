# ROADMAP.md — fwber Project Trajectory

> **Current Version:** 1.2.1 "Mobile Core Polish"
> **Last Updated:** 2026-04-04

---

## 🗺️ High-Level Trajectory

### The Pivot: Laser Focus on Core
The `fwber` platform has undergone a massive simplification (v1.2.0). We have officially removed all bloat, including:
- Decentralized Governance (DAOs, Councils, Merkle Provers)
- ActivityPub Federation (Global Social Feeds, Group Actors)
- Physical Marketplace (B2B Inventory, Tap-to-Pay, Stripe checkouts)
- Gamification (Leaderboards, Daily Streaks, Rate-My-Cat)
- AI Bloat (Wingman, AI Content Generation, Roast Generator)

The platform is now **100% focused on its core identity**:
**Privacy-first, proximity-based hookups determined by mutual preference.**

### Phase 1: Core Foundation (COMPLETED)
- Basic matching, chat, and profile systems.
- Laravel + Next.js integration.
- Database architecture and initial API.

### Phase 2: Privacy & Anti-Catfish (COMPLETED)
- ZK-Identity Verification Protocol.
- Face Reveal mechanics.
- End-to-End Encrypted Messaging.

### Phase 3: The "Local Pulse" & Safety (COMPLETED)
- High-precision Geohashing for location tracking.
- Safe Walk, Emergency Contacts, Panic Button.
- AR "Ghost" Navigation for finding matches in crowds.
- NFC Physical Tap-to-Verify (Flash Matches).

### Phase 4: Decentralization & Real-time (COMPLETED)
- Event Sourcing migration for core domains (Location, Messages, Matches).
- Laravel Reverb WebSocket integration for instant messaging.
- Offline-first IndexedDB CRDT synchronization via batch sync logic.

### Phase 5: Production Scale (ACTIVE)
- Rust `fwber-geo` microservice deployment for high-density geographic querying.
- Multi-region edge caching.
- Mobile Expo Router application finalized.

---

## 🎯 Next Immediate Milestones
1. Finalize **Push Notifications** (FCM/APNS) for the React Native app.
2. Perfect the **Battery-Efficient Background Location** tracking in the mobile app.
3. Polish the **E2E Encryption Keys** flow for seamless multi-device syncing.
