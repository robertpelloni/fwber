# MEMORY.md — Ongoing Observations & Design Preferences

> **Last Updated:** 2026-03-07 by Gemini (Antigravity)  
> **Purpose:** Persistent memory file for AI agents to document codebase observations, user preferences, and design decisions.

---

## 🚨 Operational Mode: Launch Consolidation (Effective 2026-03-07)

**Status:** FEATURE FREEZE ACTIVE  
**Priority:** Credibility → Security → User Acquisition → Stability  
**Forbidden:** New features without user request, version drift, secret commits.

**Agent Rules:**
1. **VERSION File is Canonical:** All docs must match `VERSION` file.
2. **Security First:** Flag any potential secrets immediately.
3. **No Sycophancy:** Validate claims with test coverage links.
4. **Marketing > Engineering:** Prioritize tasks that drive user acquisition.
5. **Doc Hygiene:** No new session handoff files. Log in `CHANGELOG.md` only.

---

## 🧠 User Preferences (Robert Pelloni)

### Communication Style
- Prefers **extreme autonomy** — agents should proceed without asking for confirmation unless truly destructive.
- Values **enthusiasm and momentum** — "Keep going! Don't ever stop! Don't ever quit!"
- Wants **all model instruction files** to reference `docs/UNIVERSAL_LLM_INSTRUCTIONS.md`.

### Code & Architecture Preferences
- **Monorepo monolith** (Laravel 12 backend + Next.js 16 frontend).
- **Dark mode / "Cyber-Noir" aesthetic** — gradients, glassmorphism, neon accents.
- **framer-motion** for animations.
- **Feature flags** in `config/features.php`.
- **Privacy-first** — fuzzy location, ghost mode, AI avatars by default, E2E encryption.

---

## 🏗️ Codebase Observations

### Backend (Laravel 12)
- **78 controllers**, **53 services**, **200+ test files**.
- `config/features.php` has 12+ feature flags, most disabled by default.
- Real-time via Laravel Reverb (WebSocket broadcasting).

### Frontend (Next.js 16)
- **100+ pages**, **150+ components**.
- Uses `useWebSocket` hook for real-time.
- PWA-enabled with `sw-push.js` service worker.
- `layout.tsx` reads version from `process.env.NEXT_PUBLIC_PROJECT_VERSION`.

### Infrastructure
- **Docker**: dev and prod compose files.
- **Kubernetes**: Full manifest suite ready.
- **CI/CD**: GitHub Actions (backend tests + frontend build).
- **Hosting**: DreamHost Shared (current).

---

## 📐 Design Decisions

| Decision | Rationale |
|----------|-----------|
| AI Avatars by default | Anti-catfish — reveal after connection established |
| 5-tier relationship reveal | Gradual trust-building prevents harassment |
| Proximity-first discovery | "Local Pulse" prioritizes nearby connections |
| Monorepo over microservices | Easier to deploy and maintain for small team |
| Feature flags | Gate experimental features; disable unverified ones |

---

*This file is maintained by all AI agents. Add observations as you discover them.*
