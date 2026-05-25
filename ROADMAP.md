# ROADMAP.md — fwber Project Trajectory

> **Current Version:** 2.0.16 "ActivityPub Hardened"
> **Last Updated:** 2026-05-23

---

## 🗺️ High-Level Trajectory

### The Autonomous System Integration (v2.0.18)
The project has successfully integrated the autonomous execution protocol into the main system with real-time monitoring capabilities.

**Synchronization Highlights:**
- **Observability**: A dedicated monitoring dashboard allows for real-time tracking of AI agent operations.
- **Dynamic Control**: Automated adjustment protocols can now be fine-tuned via the administrative interface.
- **Resilience**: The integration is verified through full system builds and dedicated regression tests.

### The Repository Unification (COMPLETED - v2.0.17)
The project successfully reconciled all active feature branches into the 'main' line.

That simplification intentionally retired large systems first so the repo could return to a healthy baseline around:
- proximity-based matching
- private messaging
- safety tooling
- lean onboarding/profile flows

### The Controlled Restoration Wave (COMPLETED)
After the simplification stabilized, selected systems were restored in a compact and production-oriented form based on explicit user approval.

Restored:
- AI roast / wingman surface
- premium billing and subscriptions
- merchant marketplace and storefront tooling
- geo-aware merchant discovery
- merchant trust scoring and moderation workflows

Explicitly still excluded from restoration:
- Advanced AR overlay mapping
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

### Phase 5: Production Scale (COMPLETED)
- **Merchant Inventory & Proximity Polish:** Completed the local commerce loop by adding inventory management and surfacing the real-time proximity feed.
- **Critical Production & CI Repair:** Fixed dashboard 500 error and expanded nuclear recovery to resolve production constraint failures.
- **Nuclear Schema Recovery & System Heartbeat:** Forcefully restored missing 'ghost' tables on production and activated the Laravel Scheduler on Hetzner.
- **Token Bridge & Real-time Signal:** Surfaced the Swap interface in the Economy hub and added a live Reverb network signal to the dashboard stats.
- **Mobile Notification Bridge & Marketplace Surfacing:** Restored the native push bridge for the mobile shell and surfaced the physical item marketplace in the Commerce hub.
- **Gamification & Physical-Match Surfacing:** Surfaced NFC Flash Match, AI Date Ideas, and Daily Streak celebration so the branch feels alive and engaging.
- **Sidebar Domain Organization & Exclusion Audit:** Reorganized sidebar/mobile navigation into domain groups and enforced strict exclusion of Icebreakers/Journals in the UI.
- **Hub Completion & Leaf Recovery:** Surfaced the remaining restored features (Ice Breakers, Video Calls, Hardware Token) in their respective hubs and repaired building-breaking imports.
- **Surface Polish & Runtime Hardening:** Hardened `VectorService` against missing RediSearch, surfaced `/rate-my-pussy`, `/groups/matching`, and `/gifts/history` in their respective hubs, and removed excluded links from Settings.
- **Production Schema Repair:** Added a corrective migration to eliminate 500 errors caused by 'ghost' migrations; ensuring the production database actually holds the tables and columns required by the restored codebase.
- **Rewind Shell Product-Map Consistency:** Locked in consistent domain-based organization across the dashboard and app shell so the restored branch reads as one coherent product map.
- **Rewind Sidebar Domain Organization:** Reorganized the sidebar and mobile restored-surfaces navigation so the app shell mirrors the dashboard’s domain-based product map.
- **Rewind Dashboard Domain Organization:** Reorganized the signed-in dashboard so restored surfaces are grouped by product domain rather than presented as a flat recovery list.
- **Rewind Matching Hub Recovery:** Added a real top-level matching hub for recommendations, matches, match dashboard, who-likes-you, profile-view signals, and nearby dating context so the core attraction funnel feels intentionally restored.
- **Rewind Support Hub Recovery:** Added a real top-level support hub for help, contact, privacy policy, terms, safety resources, and blocked-user controls so the trust-information layer feels intentionally restored.
- **Rewind Plans Hub Recovery:** Added a real top-level plans hub for events, event creation, date planning, nearby discovery, venues, and deals so the real-world outing layer feels intentionally restored.
- **Rewind Commerce Hub Recovery:** Added a real top-level commerce hub for merchant onboarding, dashboard operations, profile, promotions, analytics, vibe broadcasting, and adjacent business controls so the merchant/local-commerce layer feels intentionally restored.
- **Rewind Economy Hub Recovery:** Added a real top-level economy hub for premium, wallet, referrals, boosts, gifts, and unlock-related monetization flows so the token/premium layer feels intentionally restored.
- **Rewind Identity Hub Recovery:** Added a real top-level identity hub for profile, photos, identity settings, verification, physical-profile controls, and security/recovery access so the self-presentation layer feels intentionally restored.
- **Rewind Operations Hub Recovery:** Added a real top-level operations hub for safety, settings, security, merchant flows, and moderation/travel controls so the trust-and-operations layer feels intentionally restored.
- **Rewind Connections Hub Recovery:** Added a real top-level connections hub for messages, friends, activity, notifications, matches, and groups so the direct-social layer feels intentionally restored.
- **Rewind Studio Hub Recovery:** Added a real top-level studio hub for roast, roast-date, content generation, wingman, bounties, and analytics so the AI/creative/viral cluster feels intentionally restored.
- **Rewind Scenes Hub Recovery:** Added a real top-level scenes/discovery hub for recommendations, groups, topics, matches, match dashboard, and leaderboard-style discovery so the community/discovery cluster feels intentionally restored.
- **Rewind Reputation Hub Recovery:** Added a real top-level reputation hub for achievements, leaderboard, profile views, verification, and adjacent trust flows so the social-proof cluster feels intentionally restored.
- **Rewind Places Hub + Avatar Provider Test Fallback:** Added a real top-level places hub for the local-discovery cluster and pinned the implicit testing fallback provider to DALL-E for rewind avatar-generation compatibility.
- **Rewind Avatar Prompt Interest Label Fix:** Reformatted normalized profile-interest values back into human-readable themed labels during avatar prompt generation, targeting another narrow restore-branch avatar-generation CI seam without affecting deployment/runtime contracts.
- **Rewind Live-Spaces Hub Recovery:** Added a real top-level live-spaces hub and surfaced chatrooms, proximity chatrooms, audio rooms, bulletin boards, local pulse, conference pulse, and burner bridge from one coherent restored destination.
- **Rewind Avatar Prompt Relation Refresh Fix:** Patched avatar prompt generation to resolve the latest stored profile row directly, targeting the remaining explicit avatar-generation backend CI failure without regressing modern Hetzner/runtime compatibility.
- **Rewind Unlock Hub + Paywall Surface Navigation Recovery:** Added a real top-level unlock hub and surfaced premium unlocks, who-likes-you, share unlocks, and photo reveals through the dashboard + restored-features navigation so the token-gated cluster feels like a coherent restored system.
- **Rewind Surface Recovery for Boosts, Gifts, Referrals, and Video:** Added real top-level pages for these restored token-era systems and surfaced them in the dashboard + restored-features navigation so the richer branch exposes more of the approved pre-simplification product again.
- **Rewind CI Repair for Avatar Requests + Recommendation Caching:** Repaired two concrete restore-branch backend CI blockers by preserving outbound avatar-generation request behavior under HTTP fakes and restoring tagged recommendation caching expected by the richer branch’s controller-caching suite.
- **Rewind Navigation Recovery + Missing Activity Surfaces:** Reworked the restore-branch app shell so approved restored surfaces are visible again, added real top-level `/activity` and `/notifications` pages, and rebuilt the dashboard around the rewind branch’s approved scope instead of excluded federation/journal-era emphasis.
- **Restore Branch CI Compatibility Sweep:** Repaired restore-branch avatar-generation config/prompt drift, tagged-cache mock compatibility, stale frontend Sentry App Router wiring, missing WASM artifact assumptions, and deprecated Sentry Next.js build options so the broader rewind branch is much closer to the earlier full-surface state while remaining buildable/testable under modern tooling.
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

### Phase 6: Polish & Hardening (ACTIVE - v1.8.27)
- **Mobile Store Prep:** Configured required Expo plugins for push notifications and location services in `mobile/app.json` to properly bundle native iOS/Android capabilities.
- **Advanced Proximity Performance Pass:** Optimized `ProximityArtifactController` to eliminate N+1 queries when calculating local pulse compatibility indicators, pre-fetching artifact counts in bulk.
- **Extended Performance Monitoring Pass:** Optimized `MatchController` and `ProfileViewController` to eliminate N+1 database queries on core dating loops, prefetching necessary related user counts and profiles in single lookups.
- **Performance Monitoring & N+1 Query Optimization:** Refactored the dashboard `getActivity` method to batch related user lookups, completely eliminating N+1 queries from the most frequently hit route in the app.
- **AI Prompt Tuning & Localization:** Refined AI Wingman prompts across the board to deeply ground LLM instructions in Detroit's local geography and the platform's "privacy-first" identity.
- **Project Consolidation & Future Milestones:** Completed broad product polish across all hubs and defined next-phase milestones for growth.

- **ActivityPub Hardening (ACTIVE - v2.0.16):**
  - **Unified Activity Center**: Implemented a central ActivityPub activity aggregator endpoint in the backend and rewired the frontend Activity Center to use it.
  - **Enhanced Inbox Processing**: Added support for `Like` and `Announce` (Boost) activities in the `FederationService`, including automated detection of interactions with local outbox content.
  - **Handshake Completion**: Implemented signed 'Accept' activities back to remote servers to complete the follow handshake. (v2.0.15)
  - **Outbox Automation**: Wired proximity artifact creation to ActivityPub broadcasting. (v2.0.15)

---

## 🎯 Next Immediate Milestones
1. **Engagement Push:** Leverage the restored Referral & Payout system to drive early adopter signups.
2. **Performance Monitoring Pass:** Systematically optimize API latency across newly restored hub routes using live APM signals. ✅
3. **Mobile Store Prep:** Verify all native Expo capabilities (NFC, Push) against final iOS/Android store guidelines for distribution.
4. **Fediverse Interop Testing:** Verify end-to-end handle discovery and activity delivery with external servers (Mastodon/Pleroma).
