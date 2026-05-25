# Submodule & Component Versions Dashboard

> **Last Updated:** 2026-03-06 by Gemini (Antigravity)
> **Core Version:** 1.3.2

## Architectural Scope: Pure Monorepo

The **fwber** project operates as a **Pure Monorepo**. There are no Git Submodules linked to this repository. All components (Frontend, Backend, Deployment scripts) are tracked within a single root `.git` tree.

### Logical Components & Dependencies

While there are no git submodules, the project is internally divided into two distinct environments powered by package managers:

#### 1. Frontend (`/fwber-frontend`)
*   **Framework:** Next.js 16 (React 19 RC)
*   **State Management:** React Query, Zustand
*   **Real-time:** Pusher-js (bridging legacy Mercure patterns)
*   **Styling:** TailwindCSS, Shadcn UI, Framer Motion
*   **Map/Geospatial:** Leaflet & React-Leaflet
*   *(Run `npm list` in `/fwber-frontend` for exact live dependency versions)*

#### 2. Backend (`/fwber-backend`)
*   **Framework:** Laravel 12.x (PHP 8.3+)
*   **Database:** PostgreSQL + PostGIS extension
*   **Authentication:** Laravel Sanctum (Token-based)
*   **Real-time Server:** Laravel Reverb / Pusher compatible layer
*   **AI Integrations:** `openai-php/client`
*   *(Run `composer show` in `/fwber-backend` for exact live vendor versions)*

#### 3. Geo-Screener Microservice (`/fwber-geo`)
*   **Language:** Rust (2024 Edition)
*   **Framework:** Actix-Web 4.x
*   **Geospatial:** h3o (Uber H3 bindings)
*   *(Run `cargo tree` in `/fwber-geo` for exact live dependency versions)*

#### 4. Mobile Shell (`/mobile`)
*   **Framework:** Expo SDK 55 (React Native 0.83)
*   **Bridge:** react-native-webview 13.x, expo-location
*   *(Run `npx expo config` in `/mobile` for exact live config)*

#### 5. Infrastructure (`/docker`, `/kubernetes`)
*   **Containers:** Docker, Docker Compose
*   **Orchestration:** Kubernetes Configs (Ingress, Services, StatefulSets)
*   **CDN/Edge:** Cloudflare (Backend API), Vercel Edge Network (Frontend)

### Updating Dependencies

To update the internal packages (not submodules):
1. **Frontend:** `cd fwber-frontend && npm update`
2. **Backend:** `cd fwber-backend && composer update`

*This dashboard serves as the authoritative architectural mapping standard for all AI agents and CI/CD pipelines.*
