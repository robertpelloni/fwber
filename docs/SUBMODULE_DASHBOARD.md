# FWBER Submodule & Repository Dashboard

> **Generated on:** 2026-04-05
> **Current Global Version:** 1.9.6

This dashboard lists the active logical packages inside the `fwber` monorepo after the product simplification. The repository is now intentionally centered on the privacy-first local matching loop rather than federation, tokenomics, governance, or marketplace systems.

## 🗂️ Project Directory Structure

```text
fwber/
├── docs/                      # Product, deployment, AI workflow, testing, and architecture documentation
├── fwber-backend/             # Laravel 12 API for auth, onboarding, matching, messaging, safety, notifications
├── fwber-frontend/            # Next.js app-router web client for discovery, chat, profile, settings, safety
├── fwber-geo/                 # Rust geospatial microservice for nearby-user lookup and geo screening
├── fwber-wasm/                # Rust/WASM crypto helpers used by the web client where available
├── kubernetes/helm/fwber/     # Helm chart for backend/frontend/geo/reverb/worker deployment
├── mobile/                    # Expo / React Native shell wrapping the web experience with native capabilities
├── _archive/                  # Retired systems preserved for reference, not active product scope
├── submodules/                # Reserved location for external submodules (currently unused)
└── .github/workflows/         # CI/CD automation for backend tests, frontend builds, and mobile release flows
```

## 📦 Active Logical Packages

### 1. `fwber-backend`
- **Version:** inherits global `1.9.6`
- **Role:** Core API and business logic.
- **Owns:** auth, onboarding, profiles, photos, proximity matching, messages, block/report safety actions, notification APIs, E2E key backup/restore.
- **Key stack:** Laravel 12, Sanctum, PHPUnit.

### 2. `fwber-frontend`
- **Version:** inherits global `1.9.6`
- **Role:** Main user-facing product surface.
- **Owns:** app shell, discovery feed, messages, profile editing, security settings, recovery prompts.
- **Key stack:** Next.js app router, React, Tailwind CSS.

### 3. `fwber-geo`
- **Version:** internal service versioned alongside repo releases
- **Role:** High-speed geospatial indexing and nearby-user lookup.
- **Notes:** validated with the geo load test documented under `docs/ai/testing/geo-service-load-test.md`.

### 4. `fwber-wasm`
- **Version:** internal package versioned alongside repo releases
- **Role:** optional client-side crypto acceleration and benchmarking helpers.
- **Notes:** frontend includes graceful fallbacks when WASM tooling or artifacts are unavailable.

### 5. `mobile`
- **Version:** inherits global `1.4.5`
- **Role:** Native mobile shell for push notifications, secure token storage, NFC, and background location.
- **Key stack:** Expo, React Native WebView, expo-notifications, expo-location.

## 🔄 External Submodules
- **Status:** No active external git submodules are configured in `.gitmodules`.
- **Policy:** keep the repo lean; add external submodules only when a dependency must be tracked as source rather than via package management.

## ✅ Current Sync Reality
- Backend, frontend, mobile shell, and deployment docs now reflect the simplified core dating product.
- CI/CD workflows are present under `.github/workflows/`.
- The mobile shell now bridges foreground Expo pushes into the web app toast layer for active-session notification UX.
- The frontend app shell now exposes restored Gold, Roast, Merchant, and moderator sections directly in authenticated navigation instead of hiding them behind direct URLs.
- The frontend browser client now targets `api.fwber.me` directly in production and the backend route surface once again includes dashboard stats/activity for the authenticated shell.
- Notification routes are now standardized across backend payloads, notification drawer links, and toast CTA actions.
- The frontend Sentry integration now follows current App Router instrumentation conventions and no longer emits Sentry-specific build warnings.
- Active frontend pages no longer prefetch retired routes like `/roast` or `/rate-my-pussy`, and analytics event ingestion is live again in the simplified backend route set.
- The index-optimization migration is now deployment-safe across retry scenarios because it checks both for pre-existing indexes and for missing referenced columns before altering tables.
- AI and payment service providers have been restored into the active backend container graph as the first step of selective feature re-expansion.
- The AI Wingman/roast route surface plus the public `/roast` page are active again, backed by the restored `viral_contents` table/model.
- Premium billing is active again through restored `payments` / `subscriptions` schema, premium API routes, `/premium`, `/premium/success`, `/settings/subscription`, and a minimal Stripe webhook surface.
- Merchant commerce is active again through restored merchant schema, merchant portal routes/pages, storefront purchases, redemption codes, and digital receipts.
- Active deployment guidance now targets **Vercel + Hetzner**; DreamHost docs are retained only as legacy reference.
- Copy-ready Hetzner operational assets now live under `ops/hetzner/` for Nginx, systemd, and shell-based provisioning/deploy flows.
- Hetzner operations now also include a reusable `ops/hetzner/scripts/smoke-check.sh` script that verifies the public deploy contract, optional authenticated surfaces, and optional Reverb websocket upgrades.
- Smoke-check runs can now emit JSON and Markdown report artifacts, and the deploy script stores timestamped evidence under `logs/deploy-reports/` during opt-in smoke-checked redeploys.
- Smoke-check reports now also include remediation-oriented diagnostics that explain likely causes for common drift patterns like stale backend routes and geo-domain misrouting.
- Smoke-check reports now also capture endpoint fingerprints such as remote IPs, `Server` headers, redirect targets, and body excerpts for faster routing/debug analysis.
- Smoke-check reports now also capture DNS resolution records for the public frontend/API/geo/websocket hosts, making host-to-responder drift easier to prove.
- Smoke-check runs can now also be compared across deploys via drift JSON/Markdown artifacts, making it easier to detect what changed between report generations.
- Smoke-check/diff outputs can now also be condensed into compact notification artifacts and optionally posted to a webhook for chatops-style deploy summaries.
- The fwber backend runtime is now actually deployed on Hetzner with local MySQL, Redis, Reverb, queue, and geo services active; the remaining migration work is primarily public DNS/TLS cutover.
- The deploy script now also handles non-root operator execution more safely by auto-using `sudo` for systemd/nginx actions when required.
- The smoke-check websocket probe now uses a valid RFC-compliant handshake key, eliminating a post-cutover false-negative seen during live Hetzner validation.
- Merchant discovery is now geo-aware again through persisted storefront coordinates and distance-ranked nearby marketplace responses.
- Merchant moderation and trust scoring are active again through restored moderation routes, trust-weighted ranking, and moderation dashboard merchant review tooling.
- Merchant review prioritization is active through queue priority scoring, search, and inline note workflows.
- Archived systems remain preserved under `_archive/` and should not be treated as active dependencies unless explicitly restored.
