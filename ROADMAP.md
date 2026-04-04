# ROADMAP.md — fwber Project Trajectory

> **Current Version:** 1.3.2 "Notification Route Consistency"
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

### Phase 5: Production Scale (COMPLETED - v1.3.2)
- **Notification Route Consistency:** Backend notification payloads, notification drawer links, foreground toast CTAs, and `/messages` routing now agree on shared destination logic.
- **Conversation-Aware Message Notifications:** Message pushes now open the relevant conversation route (`/messages?user={id}`) instead of dropping users into a generic inbox.
- **Foreground Notification UX:** Expo foreground pushes now bridge into the WebView and render in-app match/message toasts during active mobile sessions.
- **Cold-Start Push Routing:** The native shell checks the last tapped notification on launch so deep linking remains reliable when the app is opened from a push.
- **Trust & Safety Hardening:** Blocking now severs discovery visibility, established matches, and messaging access instead of behaving like a superficial preference flag.
- **Safety Contract Repair:** Frontend block requests now match the backend validator contract, and the unblock endpoint is exposed in the active API surface.
- **Legacy Discovery Cleanup:** The active match feed no longer relies on archived `followedTopics` relations or the removed `date_feedback` table during core ranking.
- **Native Push Linking:** The Expo mobile app correctly interprets push notification JSON objects and executes deep linking into the `RealTimeChat.tsx` or `ProfileViewModal.tsx`.
- **E2E Global UX:** Persistent `<E2ERecoveryAlert />` injected into `<ProtectedRoute />` protects users from losing chat history across device upgrades.
- **Geo-Service Load Testing:** Artisan command simulated 10,000 concurrent users against the Rust microservice (Avg: 1.5ms).
- **Database Optimization:** Migrated `optimize_core_indexes` to ensure spatial, conversational, and matchmaking indices scale to millions of rows seamlessly.
- **E2E Photo Hydration:** WebWorkers explicitly wired into the `RealTimeChat.tsx` and `ProfileViewModal.tsx` interfaces to offload AES-GCM photo decryption for full galleries.
- **Native Permissions & EAS / Fastlane:** Ghost pings resolved, `eas.json` generated, Fastlane `Fastfile` automated, and location permission strings embedded in `app.json`.
- **CI/CD Build Pipelines:** GitHub Actions implemented for automated PHPUnit testing, Vercel/Next.js deployments, and Mobile EAS Store distributions.
- **Data Minimization:** Explicit S3 / object storage wipe triggers automatically upon account deletion to prevent "ghost files".

---

## 🎯 Next Immediate Milestones
1. **TestFlight Beta:** Have actual users test the TestFlight IPA compiled by the new GitHub Actions workflow.
2. **App Store Assets:** Generate fresh screenshots emphasizing the privacy-first, hyper-local nature of the simplified application.
3. **Sentry Build Cleanup:** Modernize the Next.js Sentry instrumentation/config so production builds stop emitting known deprecation warnings.
