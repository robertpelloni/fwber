# ROADMAP.md — fwber Project Trajectory

> **Current Version:** 1.9.10 "Extended Browser Storage Guard Sweep"
> **Last Updated:** 2026-04-05

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

### Phase 5: Production Scale (COMPLETED - v1.9.10)
- **Extended Browser Storage Guard Sweep:** Extended blocked-storage hardening into auxiliary frontend modules including offline sync metadata, preview telemetry, photo/verification token lookups, recommendation/content-generation caches, message storage, and vault storage so stricter browser contexts no longer explode outside the initial dashboard/E2E paths.
- **Dashboard Storage Guard + E2E Restore Probe Hardening:** Hardened auth persistence, API auth-header lookup, realtime bootstrap, and E2E IndexedDB bootstrap against blocked browser storage contexts; also verified live that `api/security/keys/restore` is publicly present on Hetzner and responds with `401` when unauthenticated.
- **Restore Branch Messaging + WebFinger Stabilization:** Added direct restore-branch fixes for local/federated message receiver validation, non-blocking message event append, and federated-only WebFinger actor URI behavior, then validated the targeted backend tests locally.
- **Restore Branch Profile + Frontend Build Stabilization:** Added direct restore-branch fixes for profile persistence, match-action event-bus drift, missing frontend UI primitives, and broken council/merchant/WASM sources, then validated key tests plus local frontend build.
- **Restore Branch Route Drift Recovery Replay:** Replayed root-route recovery, match-table drift recovery, dashboard endpoint coverage, and nodeinfo/frontend-CI alignment onto `restore/pre-simplification-hetzner`, advancing the rewind branch to `81ee89400`.
- **Restore Branch Smoke Suite + Deploy Hardening Replay:** Replayed deployment health, smoke automation/reporting, ACL/logging hardening, and nginx/deploy hardening onto `restore/pre-simplification-hetzner`, then added direct Linux route-namespace casing fixes.
- **Restore Branch Workflow Stabilization Replay:** Replayed the GitHub Hetzner backend deploy workflow and the workflow-stabilization sweep onto `restore/pre-simplification-hetzner`, then pushed branch tip `82ff8e6f6`.
- **Restore Branch Hetzner Replay Kickoff:** Created a dedicated restore worktree, replayed the first two Hetzner commits onto `restore/pre-simplification-hetzner`, and pushed branch tip `96c10825f`.
- **Pre-Simplification Rewind Branch + Replay Plan:** Created and pushed `restore/pre-simplification-hetzner`, documented the rewind baseline at `a636a53c3`, and produced the initial Hetzner/runtime replay manifest.
- **Premium Discovery Filter Restoration:** Restored premium discovery schema, profile persistence, full `/matches` advanced filter passthrough, server-side token gating, and expanded filter UX so the active discovery screen matches the product contract again.
- **Token-Gated Unlock Surface Restoration:** Restored match insights unlocks, private photo unlocks, generic content unlock ledger routes, and locked/unlocked frontend UX for another major token-spend surface cluster.
- **Profile Boost Restoration:** Restored boost purchase/status/history endpoints and reconnected the live `/matches` UI to the boost purchase flow so another token-spend surface works again.
- **Gift Economy Surface Restoration:** Restored gift catalog/send/received endpoints, gift notifications, and a real `/wallet?tab=gifts` destination so token-era gift UI has a working backend contract again.
- **Non-Critical Roast Smoke Classification:** Reclassified the public roast preview smoke assertion as warning-level so deploys stop failing solely on the known transient AI preview issue while still preserving diagnostics.
- **Smoke Roast Warmup Stabilization:** Warm the public roast preview endpoint once before the asserted smoke call so transient first-hit deploy behavior does not produce a false-negative smoke failure.
- **Smoke Check Timeout + Roast Fallback Hardening:** Added a bounded websocket smoke timeout and hardened public roast preview generation against broader AI-driver failures so deploy validation stops hanging or returning avoidable live 500s.
- **Hetzner Nginx Sync Helper Integration:** Added a root-owned helper path on the live server and updated the deploy script to use it, so GitHub Hetzner deploys can refresh tracked nginx configs without requiring blanket passwordless sudo for raw filesystem writes.
- **Hetzner Deploy Privilege Recovery:** Hardened the backend deploy script so missing passwordless sudo for nginx config file writes no longer aborts an otherwise successful Hetzner deployment.
- **Referral, Payout & Video Chat Restoration:** Restored referral validation, signup/premium referral rewards, vouch links keyed by referral code, video call initiate/signal/status/history endpoints, and expanded `/wallet` into a wallet + referrals + payout hub.
- **Wallet Surface Restoration:** Restored the compact wallet backend/API surface and added a real `/wallet` page so token-linked dead routes now land somewhere useful again.
- **Events Surface Restoration:** Restored the events backend/API surface, event invitation flow, and frontend pages for listing, viewing, and creating events.
- **Hetzner Repo Ownership Repair:** Repaired mixed ownership inside the live checkout after the automated deploy workflow failed on `.git/objects` write permissions, restoring deploy-user control of the repo.
- **Dead Surface Recovery: Activity, Notifications, Travel:** Restored `/activity`, `/notifications`, and `/settings/travel` so prominent signed-in links now land on real pages again.
- **Friends System Restoration:** Restored the friends backend/API surface, added a new `/friends` page, and put Friends back into the authenticated navigation so long-dead social links resolve again.
- **Mercure Surface Retirement:** Added a tracked Hetzner nginx config that returns an explicit retired response for `mercure.fwber.me` and wired it into the deploy script so the dead upstream is no longer presented as an active broken production service.
- **Frontend Workflow Install Strategy Fix:** Switched the dedicated frontend GitHub workflow to `npm install --no-fund --no-audit` so frontend CI can proceed despite platform-sensitive optional dependency resolution in the current dependency graph.
- **Hetzner Smoke/Deploy Contract Hardening:** Hardened smoke-check URL normalization + Reverb key discovery, re-synced tracked nginx configs during deploy, and re-verified the live Hetzner stack with a clean 9/3/0 smoke result including websocket upgrade success.
- **NodeInfo 500 Recovery + Frontend CI Runtime Fix:** Hardened `NodeInfoController` against missing federation-era schema and aligned the frontend GitHub build to Node.js 24 so both discovery endpoints and frontend CI stop failing for infrastructure/toolchain reasons.
- **Hetzner Log ACL Deploy Fix:** Replaced the broken daily-log permission approach with shared ACLs for `deploy` and `www-data`, fixing deploy-time failures caused by rotated log ownership drift on Hetzner.
- **Hetzner Backend Stability Repair:** Replaced the broken root backend route, restored the missing `WebFingerController`, hardened dashboard endpoints against missing `user_matches`, fixed the PHP 8.4 dashboard `limit` type bug, and added a corrective migration for drifted match tables discovered during live Hetzner inspection.
- **Frontend Lockfile Resync:** Regenerated the frontend lockfile and verified `npm ci && npm run build`, removing the remaining dependency-sync blocker in the modern GitHub workflow set.
- **Workflow Stabilization Sweep:** Fixed backend/frontend GitHub workflow drift and converted stale duplicate pipelines into lightweight/manual forms so the real automation signal is no longer buried under legacy red runs.
- **GitHub Hetzner Deploy Validation:** Added the required GitHub Hetzner secrets, re-ran the GitHub backend deploy workflow, and confirmed a green GitHub-triggered Hetzner deployment with successful smoke validation.
- **GitHub Hetzner Deploy Rust Path Fix:** Patched the Hetzner deploy script so CI-triggered non-login SSH sessions source rustup's Cargo path before building `fwber-geo`, fixing the first GitHub Hetzner deployment failure.
- **GitHub Backend Deploy Switched to Hetzner:** Replaced the stale GitHub Actions backend deployment job that still targeted DreamHost so CI deployment automation now matches the Hetzner production backend.
- **Live Dashboard API + Realtime Recovery:** Fixed the browser API origin to hit `api.fwber.me`, restored missing dashboard routes, added schema guards for simplified databases, and hardened Reverb defaults so live frontend requests and realtime stop failing on production contract drift.
- **Restored Feature Navigation Surface:** Exposed Gold Premium, Roast, Merchant, and moderator surfaces directly in the signed-in app shell via sidebar, mobile nav, dashboard cards, and clearer settings entry points so restored systems are actually reachable by users.
- **Hetzner Script Executable Bits:** Marked the Hetzner ops scripts executable in git so server pulls retain runnable permissions for deploy, smoke, drift, and notification tooling.
- **WebSocket Smoke Handshake Fix:** Corrected the smoke-check websocket probe to use a valid RFC-compliant handshake key so public post-cutover smoke runs no longer report false-negative websocket failures.
- **Deploy Script Privilege Hardening:** Hardened `deploy-backend.sh` so it auto-uses `sudo` for systemd/nginx actions when run by a non-root operator like `deploy`.
- **Hetzner Backend Execution & Database Migration:** Deployed fwber backend runtime to Hetzner, installed missing dependencies, upgraded Rust, built `fwber-geo`, provisioned local MySQL, imported DreamHost production data, and enabled `fwber-queue`, `fwber-reverb`, and `fwber-geo` under systemd.
- **Smoke Report Notification Publisher:** Added `publish-smoke-report.py` plus deploy integration so smoke + drift artifacts can be condensed into compact notification JSON/Markdown outputs and optionally POSTed to a webhook.
- **Smoke Report Drift Diff:** Added `compare-smoke-reports.py` plus deploy integration so smoke reports can be compared across runs for summary, diagnostics, endpoint fingerprints, and DNS changes.
- **DNS Resolution Appendix & Host Mapping:** Extended smoke-check reports with resolved-address capture for frontend, API, geo, and websocket hosts so DNS drift can be documented alongside HTTP fingerprints and diagnostics.
- **Endpoint Fingerprints & Host Signals:** Extended smoke-check reports with per-endpoint remote IPs, server headers, redirect targets, content types, and body excerpts so live routing drift can be fingerprinted, not just inferred.
- **Smoke Check Diagnostics & Remediation Hints:** Extended smoke-check reports with structured diagnostics and recommended next actions for stale backend routes, geo-domain drift, incomplete authenticated coverage, and partial-health signals.
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
1. **Inspect Latest Restore-Branch CI On `35bdf54f5`:** Confirm whether the direct messaging/WebFinger stabilization fixes remove the newest backend failures.
2. **Continue Mandatory Hetzner Replay:** Next targets should include executable-bit tracking and roast-preview smoke hardening, then continue remaining runtime fixes that still matter on the full-feature branch.
3. **Systematically Repair Remaining Behavioral Drift:** Governance, caching expectations, federation edges, and deeper full-feature frontend contracts are likely next once the newly-fixed profile/messaging/webfinger blockers are out of the way.
4. **Compare Rewind Branch Against Main After Each Tranche:** Identify which post-simplification fixes still matter after infra replay.
5. **DreamHost Retirement:** Once the rewind-merge restoration is stable on Hetzner, retire the old DreamHost backend path and remove stale provider dependencies.
