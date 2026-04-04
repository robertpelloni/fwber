# ROADMAP.md — fwber Project Trajectory

> **Current Version:** 1.4.8 "Smoke Check Report Artifacts & Live Drift Detection"
> **Last Updated:** 2026-04-04

---

## 🗺️ High-Level Trajectory

### The Pivot: Laser Focus on Core (COMPLETED)
The `fwber` platform underwent a major simplification during v1.2.x to remove unstable sprawl and recover a coherent, production-usable center.

That simplification intentionally retired large systems first so the repo could return to a healthy baseline around:
- proximity-based matching
- private messaging
- safety tooling
- lean onboarding/profile flows

### The Controlled Restoration Wave (ACTIVE / PARTIALLY COMPLETED)
After the simplification stabilized, selected systems were restored in a compact and production-oriented form based on explicit user approval.

Restored:
- AI roast / wingman surface
- premium billing and subscriptions
- merchant marketplace and storefront tooling
- geo-aware merchant discovery
- merchant trust scoring and moderation workflows

Explicitly still excluded from restoration:
- ActivityPub / Federation
- Governance / DAO / Council / On-chain systems
- Journals / Scrapbooks / Icebreakers / extra profile-social layer

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

### Phase 5: Production Scale (COMPLETED - v1.4.8)
- **Smoke Check Report Artifacts & Live Drift Detection:** Extended `ops/hetzner/scripts/smoke-check.sh` to emit JSON/Markdown reports, updated the deploy script to preserve timestamped run artifacts, and used the new automation to detect current live drift on `/api/health*` and `geo.fwber.me`.
- **Hetzner Post-Deploy Smoke Checks:** Added `ops/hetzner/scripts/smoke-check.sh`, optional authenticated and websocket probes, and deploy-script integration through `FWBER_RUN_SMOKE_CHECK=1` so public-contract validation can be repeated after real deploys.
- **Deployment Health & Verification Surface:** Added `/api/health`, `/api/health/liveness`, `/api/health/readiness`, `/api/health/metrics`, centralized dependency health evaluation, and `php artisan deploy:verify` so Hetzner cutover validation is consistent and scriptable.
- **Merchant Review Prioritization:** Added merchant moderation queue priority scoring, queue search, and inline review-note handling so storefront moderation is operationally practical.
- **Merchant Trust Scoring & Moderation:** Added merchant trust scoring, moderator review endpoints, moderation dashboard merchant queue, and trust-weighted nearby marketplace ranking so storefronts are ranked by more than proximity alone.
- **Geo-Aware Merchant Ranking:** Merchant profiles now persist storefront coordinates, nearby marketplace inventory can sort by real user-to-merchant distance, and AR overlays consume returned merchant coordinates instead of fake demo offsets.
- **Hetzner Ops Templates & CI Env Alignment:** Added copy-ready Hetzner Nginx/systemd/script assets under `ops/hetzner/` and corrected frontend CI/env examples to match the active API + Reverb contract.
- **Hetzner Deployment Docs Refresh:** Replaced DreamHost-first deployment guidance with Vercel frontend + Hetzner VPS backend documentation across root ops docs and deployment references, aligning operations with the restored active stack.
- **Marketplace & Merchant Restoration:** Restored compact merchant commerce infrastructure with merchant registration/profile/dashboard/inventory/analytics endpoints, merchant storefront pages, purchase/redemption flow, digital receipts, and `merchant_profiles` / `merchant_inventories` / `merchant_payments` / `inventory_redemptions` schema.
- **Premium & Billing Restoration:** Restored `PremiumController`, `StripeWebhookController`, `Payment`, `Subscription`, the `payments`/`subscriptions` schema, `/premium`, `/settings/subscription`, `/premium/success`, and the repaired who-likes-you premium flow.
- **AI Surface Restoration:** Restored `AiWingmanController`, `ViralContent`, the `viral_contents` schema, and the public `/roast` page, reconnecting active UI and API flows for roast/hype generation.
- **Restoration Foundation:** `AiServiceProvider` and `PaymentServiceProvider` are restored to the active backend, preparing safe reactivation of AI and monetization systems without unresolved container dependencies.
- **Migration Column Guards:** The `optimize_core_indexes` migration now skips index definitions whose required columns are missing, preventing deploy failures on drifted schemas like `photos` tables missing `order`.
- **Deployment Migration Idempotency:** The `optimize_core_indexes` migration now checks for existing indexes before creation, so partial MySQL deploys can be retried without duplicate-key failures.
- **Console Error Sweep:** Removed stale archived-route prefetches, restored analytics event ingestion, repaired notification settings routing, and hardened auth response parsing against malformed server bodies.
- **Sentry Build Modernization:** The Next.js App Router Sentry setup now uses modern `instrumentation.ts` and `instrumentation-client.ts` hooks, eliminating outdated warning noise from production builds.
- **Notification Route Consistency:** Backend notification payloads, notification drawer links, foreground toast CTAs, and `/messages` routing now agree on shared destination logic.
- **Foreground Notification UX:** Expo foreground pushes now bridge into the WebView and render in-app match/message toasts during active mobile sessions.
- **Trust & Safety Hardening:** Blocking now severs discovery visibility, established matches, and messaging access instead of behaving like a superficial preference flag.
- **E2E Global UX:** Persistent `<E2ERecoveryAlert />` injected into `<ProtectedRoute />` protects users from losing chat history across device upgrades.
- **Geo-Service Load Testing:** Artisan command simulated 10,000 concurrent users against the Rust microservice (Avg: 1.5ms).
- **Database Optimization:** Migrated `optimize_core_indexes` to ensure spatial, conversational, and matchmaking indices scale to millions of rows seamlessly.
- **CI/CD Build Pipelines:** GitHub Actions implemented for automated PHPUnit testing, Vercel/Next.js deployments, and Mobile EAS Store distributions.
- **Data Minimization:** Explicit object-storage wipe triggers automatically upon account deletion to prevent ghost files.

---

## 🎯 Next Immediate Milestones
1. **Hetzner/Vercel Deployment Execution:** Stand up the planned VPS + frontend deployment topology using the `ops/hetzner/` templates, then verify the stack with `php artisan deploy:verify`, `ops/hetzner/scripts/smoke-check.sh`, and the generated report artifacts before deeper UX testing.
2. **Fix Current Live Routing Drift:** Ensure the reachable `api.fwber.me` backend actually serves `/api/health*` and that `geo.fwber.me` points to a live geo-service deployment rather than a Vercel 404.
3. **Production Stripe Verification:** Confirm live Stripe checkout + webhook handling for both premium and marketplace purchases in a real authenticated deployment environment.
4. **Smoke-Test Credential Strategy:** Provision safe premium/merchant/moderator smoke tokens and Reverb key access so the optional smoke-check probes can run at full depth.
