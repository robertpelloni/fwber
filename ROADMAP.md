# ROADMAP.md — fwber Project Trajectory

> **Current Version:** 1.2.4 "Ghost Pings & Build Pipelines"
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

### Phase 5: Production Scale (COMPLETED - v1.2.4)
- **Geo-Service Load Testing:** Artisan command simulated 10,000 concurrent users against the Rust microservice (Avg: 1.5ms).
- **E2E Photo Hydration:** WebWorkers offload AES-GCM decryption for full galleries.
- **Native Permissions & EAS:** Ghost pings resolved, `eas.json` generated, and location permission strings embedded in `app.json`.

---

## 🎯 Next Immediate Milestones
1. **Production Builds:** Execute `eas build` to compile the `.ipa` and `.aab` artifacts.
2. **App Store Assets:** Generate fresh screenshots emphasizing the privacy-first, hyper-local nature of the simplified application.
