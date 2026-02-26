# MEMORY.md — Ongoing Observations & Design Preferences

> **Last Updated:** 2026-02-26 by Claude (Antigravity)  
> **Purpose:** Persistent memory file for AI agents to document codebase observations, user preferences, and design decisions that should carry forward across sessions.

---

## 🧠 User Preferences (Robert Pelloni)

### Communication Style
- Prefers **extreme autonomy** — agents should proceed without asking for confirmation unless truly destructive.
- Loves **enthusiasm and momentum** — "Keep going! Don't ever stop! Don't ever quit!"
- Values **comprehensive documentation** — every feature, version, and detail should be recorded.
- Wants **all model instruction files** to reference a single `docs/UNIVERSAL_LLM_INSTRUCTIONS.md`.
- Expects **version bumps on every session** with commit messages referencing the new version.

### Code & Architecture Preferences
- **Monorepo monolith** (Laravel 12 backend + Next.js 16 frontend) — no microservices unless there's a clear performance need.
- **Dark mode / "Cyber-Noir" aesthetic** — gradients, glassmorphism, neon accents, premium feel.
- **framer-motion** for animations — used extensively in Tier Unlock, Roast Share, Vouch Leaderboard.
- **Feature flags** in `config/features.php` — features are gated and can be toggled.
- **Privacy-first** — fuzzy location, ghost mode, AI avatars by default, E2E encryption.
- Prefers **comprehensive in-code comments** that explain *why*, not just *what*.

### Versioning Protocol
- Single source of truth: `VERSION` file in project root (plain text, just the version string).
- All other version references (package.json, layout.tsx, model files) should read from or be synced to this file.
- CHANGELOG.md gets a new section for every version bump.
- Commit message format: `chore(release): bump version to X.Y.Z`.

---

## 🏗️ Codebase Observations

### Backend (Laravel 12)
- **78 controllers**, **53 services**, **200+ test files**.
- `AIMatchingService` uses SQL-based keyword matching (not vector embeddings) — functional but Phase 6 upgrade planned.
- `PrivacySecurityService` supports a driver architecture (Mock/AWS/Google) but defaults to Mock.
- `LlmManager` service abstracts OpenAI/Gemini/Claude — used by ContentGeneration and AiWingman.
- APM middleware exists but was disabled in production (enabled Feb 25 with `APM_ENABLED=true`).
- The `config/features.php` file has 12+ feature flags, most disabled by default.

### Frontend (Next.js 16)
- **100+ pages** under `app/` directory, **150+ components**.
- Uses `useWebSocket` hook (unified from old `useMercureLogic` and `usePusherLogic`).
- `useRelationshipTier` hook provides real-time tier progression data.
- `framer-motion` v11+ used for animations.
- PWA-enabled with `sw-push.js` service worker, offline IndexedDB support.
- Solana integration via `@solana/web3.js` and `@reown/appkit`.
- The layout.tsx `v0.3.2` hardcoded version string needs to be made dynamic.

### Infrastructure
- **Docker**: `docker-compose.yml`, `docker-compose.dev.yml`, `docker-compose.prod.yml`.
- **Kubernetes**: Full manifest suite in `kubernetes/` directory.
- **CI/CD**: GitHub Actions in `.github/workflows/`.
- **Hosting**: DreamHost Shared (current), with Kubernetes manifests ready for cloud migration.
- **Real-time**: Transitioned from Mercure → Pusher/Reverb (Laravel Broadcasting).

### Known Quirks
- `react-leaflet` pinned to v4.2.1 (v5 has type conflicts).
- `useSearchParams()` in Next.js 16 requires `<Suspense>` boundaries or build fails during static generation.
- `AnalyticsProvider` in `app/layout.tsx` must be wrapped in `<Suspense>` (fixed Feb 26).
- Multiple lockfile warnings during build (root `package-lock.json` vs frontend `package-lock.json`).
- `bigint` binding warning during static page generation — cosmetic only.

---

## 📐 Design Decisions

| Decision | Rationale |
|----------|-----------|
| AI Avatars by default | Anti-catfish philosophy — reveal after connection established |
| Token economy (FWB Tokens) | Bridges Web2 utility with Web3 ownership (Solana) |
| 5-tier relationship reveal | Gradual trust-building prevents harassment |
| Proximity-first discovery | "Local Pulse" prioritizes nearby connections over global feeds |
| Monorepo over microservices | Easier to deploy, test, and maintain for a small team |

---

*This file is maintained by all AI agents. Add observations as you discover them.*
