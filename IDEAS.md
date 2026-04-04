# fwber Extreme Ideation & Analysis
> **Generated on:** 2026-04-02
> **Goal:** Creative, constructive analysis of the fwber monorepo to find missing features, improvements, refactoring targets, language ports, and concept pivots.

## 🔭 Architectural Refactoring & Modernization
1. **gRPC / Cap'n Proto for Backend <-> Rust Geo-Screener:** 
   Currently, the PHP backend likely communicates with `fwber-geo` via HTTP/JSON. Transitioning this to a highly packed binary protocol like Cap'n Proto or gRPC would cut serialization overhead in high-density areas dramatically.
2. **WebAssembly (WASM) for E2E Encryption:**
   Move the client-side encryption primitives from JavaScript to a compiled WASM module (built in Rust). This will prevent browser freezing when encrypting large media payloads (e.g., videos) and provide stronger memory isolation.
3. **Database Migration (PlanetScale / CockroachDB):**
   MySQL is solid, but since `fwber` relies heavily on geographic clustering and ActivityPub federation, moving to a distributed SQL database like CockroachDB could allow the proximity dating features to run globally without a single database bottleneck.

## 🌟 New Features & "Concept Pivots"
1. **"Anon-Vibe" Mode (Zero-Knowledge Dating):**
   A feature where photos are entirely obfuscated by an AI-generated artistic silhouette. Users only see the raw interests, music taste, and `compatibilityAudit` scores. Photos only "decrypt" and fade into reality once a certain number of messages have been organically exchanged.
2. **Augmented Reality "Safe Drops":**
   Since we have `MatchARView`, users could "drop" virtual gifts or encrypted voice notes at specific physical coordinates. The other user has to walk to that physical GPS coordinate to unlock and listen to the voice note.
3. **Hardware "Touch" Protocol:**
   For the BLE physical token: If two matched users wearing the token tap their wrists together, the tokens exchange a cryptographic handshake over BLE that immediately registers a "Physical Verified Meetup" in the app, boosting their trust scores massively.

## 🏗️ Repo Restructuring
1. **Nx Monorepo Port:**
   Right now the structure is just loose folders (`fwber-backend`, `fwber-frontend`, `mobile`). Porting the repository to an `Nx` workspace would allow unified dependency management, parallel testing, and shared TypeScript interfaces between the Next.js frontend and React Native mobile app.
2. **Move "Mobile" to Expo Router:**
   The `mobile` folder uses an older React Native approach. Upgrading it to Expo Router v3 would unify the file-based routing paradigm between the Next.js web app and the mobile app.

## 🌍 Language Ports
1. **Elixir / Go backend port:**
   While Laravel is great for rapid iteration, the concurrent nature of WebSockets, ActivityPub outboxes, and real-time proximity tracking might be better served by Elixir (Phoenix) or Go. A Go microservice specifically handling the WebSocket chat presence could offload massive pressure from Laravel Reverb.
2. **Rust-based Media Processing:**
   Move all image resizing, thumbnail generation, and facial recognition blur (currently done via PHP/client) into a dedicated Rust media-processing microservice using the `image` crate.

## 🛠 Missing Micro-Features
- **Message Edit/Unsend:** Provide a 5-minute window to unsend or edit E2E encrypted messages (handling the CRDT conflict resolution).
- **Federated "Blocklists":** ActivityPub allows for importing shared blocklists (#Fediblock). Implementing a tool to import community blocklists would protect users proactively.
- **Read Receipts toggle:** Users should have the option to disable read receipts globally or per-match for privacy.
## 2026-04-04 - After Premium Restoration
- Add a unified entitlement layer that can answer "is feature X premium/verified/merchant gated?" from one backend service instead of spreading gating rules across controllers.
- Add a billing diagnostics page for admins that replays webhook payloads against a sandbox user, making Stripe rollout debugging safer after staged restorations.
- Replace hard-coded plan presentation in the frontend with a shared generated contract from `config/premium.php` so product copy and backend price definitions never drift.

## 2026-04-04 - After Smoke Check Diagnostics & Remediation Hints
- Add a small secrets-backed smoke-profile generator that rotates bearer tokens for user, merchant, and moderator smoke accounts so `smoke-check.sh` can run deeper probes without relying on long-lived manual tokens.
- Extend the smoke-check script with a webhook replay mode that can safely POST saved Stripe test fixtures to a staging or sandbox backend, verifying payment handling without touching live billing.
- Add a Slack/webhook publisher that posts the generated JSON + Markdown deploy evidence after each smoke-checked redeploy, including any failing health/geodomain findings and the remediation diagnostics.

## 2026-04-04 - After Smoke Check Report Artifacts
- Add a small secrets-backed smoke-profile generator that rotates bearer tokens for user, merchant, and moderator smoke accounts so `smoke-check.sh` can run deeper probes without relying on long-lived manual tokens.
- Extend the smoke-check script with a webhook replay mode that can safely POST saved Stripe test fixtures to a staging or sandbox backend, verifying payment handling without touching live billing.
- Add a Slack/webhook publisher that posts the generated JSON + Markdown deploy evidence after each smoke-checked redeploy, including any failing health/geodomain findings.

## 2026-04-04 - After Hetzner Post-Deploy Smoke Checks
- Add a small secrets-backed smoke-profile generator that rotates bearer tokens for user, merchant, and moderator smoke accounts so `smoke-check.sh` can run deeper probes without relying on long-lived manual tokens.
- Extend the smoke-check script with a webhook replay mode that can safely POST saved Stripe test fixtures to a staging or sandbox backend, verifying payment handling without touching live billing.
- Add a deploy-report artifact mode that writes JSON + Markdown summaries from `smoke-check.sh` and `php artisan deploy:verify`, making it easy to attach cutover evidence to releases or send it into Slack.

## 2026-04-04 - After Deployment Health & Verification Surface
- Add a tiny operator dashboard that consumes `/api/health` plus the geo-service and websocket domains so a single internal page can show cross-service rollout status during cutovers.
- Add a deploy script wrapper that runs `php artisan deploy:verify --json`, parses critical failures, and emits a red/green summary suitable for CI/CD or Slack notifications.
- Add a merchant/premium synthetic-check suite that runs against production nightly using seeded smoke-test accounts, verifying premium purchase eligibility, merchant queue access, and marketplace browsing without needing full end-to-end manual testing.

## 2026-04-04 - After Marketplace Restoration
- Add merchant location persistence and a true geo-aware nearby storefront feed so `marketplace/nearby` can rank by actual distance instead of a generic latest-items fallback.
- Add merchant verification workflow screens and moderation tooling so restored storefronts can be trust-scored before wider rollout.
- Unify premium and merchant billing receipts under a shared purchase-history surface in user settings.
