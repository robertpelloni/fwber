# ROADMAP.md — fwber Project Trajectory

> **Current Version:** 1.2.3 "Production Scale & Media Workers"
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

### Phase 5: Production Scale (ACTIVE - v1.2.3)
- **Geo-Service Load Testing:** Artisan command to simulate 10,000 concurrent users against the Rust microservice.
- **E2E Photo Hydration:** WebWorkers offload AES-GCM decryption for full galleries to ensure a 60fps UI.
- **Native Permissions UX:** Improved splash screen for mobile background location and push notifications.

---

## 🎯 Next Immediate Milestones
1. **EAS Build Pipelines:** Configure Expo Application Services (Fastlane) to produce our .ipa and .aab files for App Store distribution.
2. **Ghost Ping Cleanup:** Ensure background worker tokens are cleared if a user logs out of the web interface.
