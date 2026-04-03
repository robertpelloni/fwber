# ROADMAP.md — fwber Project Trajectory

> **Current Version:** 1.2.1 "Core Performance & Mobile Polish"
> **Last Updated:** 2026-04-04

---

## 🗺️ High-Level Trajectory

### The Pivot: Laser Focus on Core (COMPLETED)
The `fwber` platform has undergone a massive simplification (v1.2.0/v1.2.1). We have officially removed all bloat, including:
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

### Phase 4: Mobile & E2E Polish (COMPLETED - v1.2.1)
- **Battery-Efficient Background Location:** OS-level tracking in Expo for proximity alerts.
- **Push Notifications:** Full integration with Expo/Laravel for real-time match/message alerts.
- **E2E Key Sync:** Secure multi-device backup and recovery of encryption keys.

---

## 🎯 Next Immediate Milestones
1. **Performance Tuning:** Optimize Rust `fwber-geo` service for high-density metropolitan areas.
2. **Media Optimization:** Implement ultra-fast media delivery for E2E encrypted photos.
3. **App Store Readiness:** Finalize remaining native bridge edge cases for iOS/Android submission.
