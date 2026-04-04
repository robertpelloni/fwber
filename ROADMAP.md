# ROADMAP.md — fwber Project Trajectory

> **Current Version:** 1.4.0 "Marketplace & Merchant Restoration"
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

### Phase 5: Production Scale (COMPLETED - v1.4.0)
- **Marketplace & Merchant Restoration:** Restored compact merchant commerce infrastructure with merchant registration/profile/dashboard/inventory/analytics endpoints, merchant storefront pages, purchase/redemption flow, digital receipts, and `merchant_profiles` / `merchant_inventories` / `merchant_payments` / `inventory_redemptions` schema.
- **Premium & Billing Restoration:** Restored `PremiumController`, `StripeWebhookController`, `Payment`, `Subscription`, the `payments`/`subscriptions` schema, `/premium`, `/settings/subscription`, `/premium/success`, and the repaired who-likes-you premium flow.
- **AI Surface Restoration:** Restored `AiWingmanController`, `ViralContent`, the `viral_contents` schema, and the public `/roast` page, reconnecting active UI and API flows for roast/hype generation.
- **Restoration Foundation:** `AiServiceProvider` and `PaymentServiceProvider` are restored to the active backend, preparing safe reactivation of AI and monetization systems without unresolved container dependencies.
- **Migration Column Guards:** The `optimize_core_indexes` migration now skips index definitions whose required columns are missing, preventing deploy failures on drifted schemas like `photos` tables missing `order`.
- **Deployment Migration Idempotency:** The `optimize_core_indexes` migration now checks for existing indexes before creation, so partial MySQL deploys can be retried without duplicate-key failures.
- **Console Error Sweep:** Removed stale archived-route prefetches, restored analytics event ingestion, repaired notification settings routing, and hardened auth response parsing against malformed server bodies.
- **Sentry Build Modernization:** The Next.js App Router Sentry setup now uses modern `instrumentation.ts` and `instrumentation-client.ts` hooks, eliminating outdated warning noise from production builds.
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
1. **Hetzner/Vercel Deployment Execution:** Stand up the planned VPS + frontend deployment topology and validate restored AI, premium, and merchant systems in the new environment.
2. **Production Stripe Verification:** Confirm live Stripe checkout + webhook handling for both premium and marketplace purchases in a real authenticated deployment environment.
3. **Redeploy After Column Guard Fix:** Re-run deployment and confirm the migration now survives both duplicate-index and missing-column drift scenarios.
