# Project Status — fwber v0.5.0-beta

**Date:** 2026-03-07  
**Version:** 0.5.0-beta  
**Status:** Beta — Stabilization & Launch Consolidation

---

## Current State

fwber is a privacy-first proximity dating platform. The core dating loop (register → onboard → discover → match → chat) is implemented across a Laravel 12 backend and Next.js 16 frontend.

### What Works (Core Loop)
- ✅ User registration and authentication (Sanctum)
- ✅ Onboarding wizard with profile creation
- ✅ AI avatar generation
- ✅ Local Pulse proximity feed (artifacts + candidates)
- ✅ Match discovery and authorization
- ✅ Direct messaging (text + audio)
- ✅ Notification system (database + push)
- ✅ Feature flag system (`config/features.php`)

### What Exists But Needs Verification
- ⚠️ Video chat (WebRTC) — needs E2E testing
- ⚠️ AI Wingman features (roast, vibe check, etc.) — need real API keys
- ⚠️ Solana token integration — needs live wallet testing
- ⚠️ Real-time WebSocket (Reverb) — needs production environment
- ⚠️ Face blur — needs browser compatibility testing

### Experimental / Aspirational (Not Verified)
- 🔬 ZK proximity proofs (code exists, unverified)
- 🔬 ActivityPub federation (scaffolded, unverified)
- 🔬 Rust geo-screener (separate binary, unverified)
- 🔬 React Native mobile wrapper (Expo shell, unverified)
- 🔬 Audio dating rooms (WebRTC SFU, unverified)
- 🔬 Burner communication bridge (scaffolded, unverified)

## Known Issues
- CI/CD: `fwber-mysql` host resolution failures in test runner (Docker networking).
- Frontend: Multiple lockfile warnings during build.
- Version display: `layout.tsx` reads from `NEXT_PUBLIC_PROJECT_VERSION` env var.

## Infrastructure
- **Hosting**: DreamHost Shared (current), Kubernetes manifests ready.
- **CI**: GitHub Actions (backend tests + frontend build).
- **Real-time**: Laravel Reverb (WebSocket broadcasting).

---

*See `CHANGELOG.md` for version history. See `docs/FEATURE_STATUS_MATRIX.md` for detailed feature categorization.*
