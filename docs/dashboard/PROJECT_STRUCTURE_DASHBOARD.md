# Project Structure Dashboard

**Last Updated:** 2026-03-06
**Version:** v1.3.2
**Maintainer:** Gemini (Antigravity)

## OVERVIEW
`fwber` is a privacy-first, proximity-based social platform operating as a monorepo with 5 logical components.
**Note:** All components are standard directories within this repository, NOT git submodules.

## DIRECTORY LAYOUT

### 📂 Root (`/`)
| Path | Description | Version |
|------|-------------|---------|
| `fwber-backend/` | Laravel 12 API & Application Logic | 1.3.2 |
| `fwber-frontend/` | Next.js 16 Web Application (PWA) | 1.3.2 |
| `fwber-geo/` | Rust Geo-Screener Microservice (Actix-Web + H3) | 0.1.0 |
| `mobile/` | React Native / Expo Mobile Shell | 0.1.0 |
| `docker/` | Docker Compose & Container Configurations | - |
| `kubernetes/` | K8s Deployment Manifests | - |
| `docs/` | Project Documentation & Knowledge Base | - |
| `VERSION` | Current Project Version | 1.3.2 |

### 🐘 Backend (`fwber-backend/`)
**Stack:** Laravel 12, PHP 8.3+, PostgreSQL + PostGIS, Redis, Pusher/Reverb
| Key Path | Purpose |
|----------|---------|
| `app/Http/Controllers/` | 60+ REST controllers (Auth, AI, ActivityPub, ZK, Audio, etc.) |
| `app/Services/` | Business logic (Matching, Avatar, ActivityPub, Token, Wingman) |
| `app/Http/Middleware/` | Edge caching, APM, throttling, daily bonus |
| `database/migrations/` | 80+ migrations (PostgreSQL + PostGIS spatial) |
| `routes/api.php` | ~200 API endpoints |
| `tests/Feature/` | PHPUnit feature tests |

### ⚛️ Frontend (`fwber-frontend/`)
**Stack:** Next.js 16.1.1, React 19, TailwindCSS, Shadcn UI, Framer Motion
| Key Path | Purpose |
|----------|---------|
| `app/` | App Router — 40+ pages (Dashboard, Audio Rooms, Burner Bridge, etc.) |
| `components/` | 50+ reusable UI components (EvolvingAvatar, ZKProver, RealTimeChat) |
| `lib/api/` | Type-safe API clients (profile, messages, chatrooms, location) |
| `lib/hooks/` | Custom hooks (useWebRTC, useWebSocket, useAuth) |

### 🦀 Geo-Screener (`fwber-geo/`)
**Stack:** Rust 2024 Edition, Actix-Web 4, h3o (Uber H3 bindings)
| Key Path | Purpose |
|----------|---------|
| `src/main.rs` | HTTP server with `/screen` and `/health` endpoints |
| `Cargo.toml` | Dependencies (Actix-Web, h3o, Serde) |

### 📱 Mobile (`mobile/`)
**Stack:** Expo SDK 55, React Native 0.83, react-native-webview
| Key Path | Purpose |
|----------|---------|
| `App.js` | WebView wrapper with Geolocation, BackHandler, external link bridging |
| `app.json` | Expo config (permissions, bundle IDs, tracking transparency) |

## KEY FEATURES (Phase 1–7)
- **Identity:** AI Avatars, Evolving Emotional Avatars, Bio Analysis
- **Engagement:** 5-Tier Relationship System, Token Economy (FWB), Photo Reveals
- **Communication:** E2E Encrypted Chat, Voice Memos, Audio Rooms, Proximity Chatrooms
- **Privacy:** ZK Proximity Proofs, Ghost Mode, Geo-Spoof Detection, Burner QR Bridges
- **AI:** Wingman Suite (Roast, Cosmic, Nemesis, Fortune, Vibe), Conversation Coach
- **Performance:** Rust H3 Geo-Screener, Edge Cache Middleware, APM Monitoring
- **Federation:** ActivityPub-compatible servers, WebFinger, NodeInfo
- **Merchant:** Sponsored Local Deals, Merchant Analytics Dashboard

## INFRASTRUCTURE
- **Docker:** Development environment in `docker/` configs
- **Kubernetes:** Production manifests in `kubernetes/`
- **CDN/Edge:** Cloudflare (API), Vercel Edge Network (Frontend)
- **CI/CD:** GitHub Actions

## DOCUMENTATION MAP
- **Vision:** [`VISION.md`](../../VISION.md)
- **Master Protocol:** [`docs/UNIVERSAL_LLM_INSTRUCTIONS.md`](../UNIVERSAL_LLM_INSTRUCTIONS.md)
- **Deployment:** [`DEPLOY.md`](../../DEPLOY.md)
- **Changelog:** [`CHANGELOG.md`](../../CHANGELOG.md)
- **Agent Instructions:** `AGENTS.md`, `CLAUDE.md`, `GEMINI.md`, `GPT.md`
