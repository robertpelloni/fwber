# Feature Status Matrix — fwber v0.5.0-beta

> **Date:** 2026-03-07  
> **Purpose:** Honest categorization of every feature's actual maturity level.

## Legend

| Status | Meaning |
|--------|---------|
| ✅ **Production** | Tested, deployed, verified working |
| ⚠️ **Beta** | Code exists and builds, needs real-world verification |
| 🔬 **Experimental** | Code scaffolded, not verified end-to-end |
| 📋 **Aspirational** | Claimed in docs but maturity unverified |

---

## Core Dating Loop

| Feature | Status | Evidence |
|---------|--------|----------|
| User Registration | ✅ Production | PHPUnit tests passing, Sanctum auth |
| Login / Logout | ✅ Production | PHPUnit tests, CSRF protection |
| Onboarding Wizard | ⚠️ Beta | Controller + frontend exist, E2E test exists |
| Profile CRUD | ✅ Production | PHPUnit tests passing |
| AI Avatar Generation | ⚠️ Beta | Service exists, requires API key (Replicate/DALL-E) |
| Local Pulse Feed | ⚠️ Beta | Controller + tests exist, needs production verification |
| Proximity Artifacts | ⚠️ Beta | CRUD + TTL + flagging tested in PHPUnit |
| Match Discovery | ⚠️ Beta | `AIMatchingService` with SQL-based scoring |
| Match Authorization | ⚠️ Beta | Controller + tests exist |
| Direct Messaging (Text) | ⚠️ Beta | Controller exists, needs WebSocket verification |
| Direct Messaging (Audio) | ⚠️ Beta | `AudioRecorder` component, upload service |

## Privacy & Security

| Feature | Status | Evidence |
|---------|--------|----------|
| Location Fuzzing | ⚠️ Beta | `GeolocationService` exists |
| Ghost Mode (Incognito) | ⚠️ Beta | Toggle in settings, backend filter |
| Account Deletion | ⚠️ Beta | `ProfileController::destroy` + tests |
| Data Export (GDPR) | ⚠️ Beta | `ProfileController::export` + tests |
| Content Flagging | ⚠️ Beta | Flag escalation system tested |
| Rate Limiting | ✅ Production | Laravel middleware configured |
| CSRF Protection | ✅ Production | Standard Laravel implementation |
| 2FA (TOTP) | ⚠️ Beta | Controller exists, E2E pending |

## Real-Time & Communication

| Feature | Status | Evidence |
|---------|--------|----------|
| WebSocket (Reverb) | ⚠️ Beta | Configured, needs production testing |
| Push Notifications | ⚠️ Beta | Service worker exists (`sw-push.js`) |
| Video Chat (WebRTC) | 🔬 Experimental | `VideoCallModal` exists, untested peer connections |
| Voice/Audio Rooms | 🔬 Experimental | SFU topology scaffolded (v0.3.38) |
| Proximity Chatrooms | ⚠️ Beta | Controller + tests exist |

## AI Features

| Feature | Status | Evidence |
|---------|--------|----------|
| AI Profile Roast | ⚠️ Beta | `AiWingmanService::roastProfile` + tests |
| AI Vibe Check | ⚠️ Beta | `AiWingmanService::checkVibe` + tests |
| AI Conversation Coach | ⚠️ Beta | Backend tests passing |
| AI Content Generation | ⚠️ Beta | `LlmManager` abstraction (OpenAI/Gemini/Claude) |
| AI Matching Recommendations | ⚠️ Beta | SQL keyword matching (not vector embeddings) |

## Economy & Monetization

| Feature | Status | Evidence |
|---------|--------|----------|
| FWB Token System | ⚠️ Beta | Internal token balance + transactions |
| Solana Wallet Bridge | 🔬 Experimental | `@solana/web3.js` integrated, untested |
| Token-Gated Chatrooms | 🔬 Experimental | Controller exists, needs payment verification |
| Token-Gated Events | 🔬 Experimental | `TokenDistributionService` exists |
| Stripe Payments | 🔬 Experimental | `StripePaymentGateway` wired, needs live testing |
| Pay-to-Unlock Photos | 🔬 Experimental | `PhotoUnlock` model exists |

## Social & Viral

| Feature | Status | Evidence |
|---------|--------|----------|
| Friend System | ⚠️ Beta | Controller + tests exist |
| Groups | ⚠️ Beta | Controller exists |
| Events | ⚠️ Beta | Controller + tests exist |
| Vouch System | ⚠️ Beta | Leaderboard component exists |
| Referral System | ⚠️ Beta | `MatchMakerService` exists |
| Viral Content Sharing | ⚠️ Beta | `ViralContentController` + share pages |

## Infrastructure

| Feature | Status | Evidence |
|---------|--------|----------|
| Docker (Dev) | ⚠️ Beta | `docker-compose.dev.yml` exists |
| Docker (Prod) | 🔬 Experimental | `docker-compose.prod.yml` exists, untested |
| Kubernetes Manifests | 🔬 Experimental | Full suite in `kubernetes/`, untested |
| CI (GitHub Actions) | ✅ Production | Backend tests + frontend build |
| PWA | ⚠️ Beta | Manifest + service worker exist |

## Unverified / Aspirational

| Feature | Status | Notes |
|---------|--------|-------|
| ZK Proximity Proofs | 📋 Aspirational | Code added v0.3.44, zero end-to-end verification |
| ActivityPub Federation | 📋 Aspirational | Endpoints scaffolded v0.3.47, no federated tests |
| Rust Geo-Screener | 📋 Aspirational | Separate binary in `fwber-geo/`, no integration tests |
| React Native App | 📋 Aspirational | Expo shell v0.3.48, no device testing |
| Multi-Region Edge | 📋 Aspirational | Middleware added v0.3.49, no CDN deployment |
| Burner Communication | 📋 Aspirational | QR relay scaffolded v0.3.39, no E2E test |
| Evolving AI Avatars | 📋 Aspirational | Emotion states added v0.3.45, untested |
| Face Blur (Video) | 📋 Aspirational | `face-api.js` integration, no browser testing |

---

*This matrix is the honest assessment. Features move from Aspirational → Experimental → Beta → Production only with test evidence.*
