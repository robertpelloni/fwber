# Submodule & Component Dashboard

> **Version:** 0.99.1
> **Last Updated:** 2026-03-25

This dashboard outlines the core components (logical submodules) of the fwber monorepo. While we do not currently use strict `.gitmodules`, these directories function as independent deployable services within the greater architecture.

---

## 🏗️ Project Directory Layout

```text
fwber/
├── docs/                  # Global architecture docs, AI instructions, and roadmaps
├── fwber-backend/         # Laravel 12 API, Admin Panel, WebSocket Engine
├── fwber-frontend/        # Next.js 16 Web App (PWA, UI/UX)
├── fwber-geo/             # Rust Spatial Microservice (High-density location queries)
├── mobile/                # React Native / Expo 55 Native App
└── kubernetes/            # K8s manifests and Helm charts for enterprise scale
```

---

## 📦 Component Registry

| Component | Path | Tech Stack | Current Version | Primary Purpose | Deployment Target |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Backend API** | `/fwber-backend` | Laravel 12, PHP 8.4, MySQL, Reverb | `v0.99.1` | Core business logic, DB interactions, WebSockets | DreamHost / AWS EC2 |
| **Frontend Web** | `/fwber-frontend` | Next.js 16, React 19, TailwindCSS | `v0.99.1` | Primary user interface, PWA, AR Views | Vercel Edge |
| **Mobile App** | `/mobile` | Expo 55, React Native | `v0.99.1` | Native iOS and Android experiences | App Store / Play Store |
| **Geo Engine** | `/fwber-geo` | Rust, Actix-web, H3 | `v0.99.1` | Millisecond spatial indexing for dense areas | Internal Microservice |

---

## 🔗 External Dependencies & Libraries
*   **Face-API.js** (`@vladmandic/face-api`): Used in `fwber-frontend` for client-side face blurring (The "Anti-Catfish" guarantee). Served statically to avoid Vercel build limits.
*   **Pusher-JS**: Used in `fwber-frontend` to connect to our self-hosted Laravel Reverb server.
*   **Shadcn/UI**: Foundational UI component library utilized extensively in the frontend.
*   **Framer Motion**: Powers all the fluid, cyber-noir animations and swipe mechanics.
*   **Sentry**: Integrated into both frontend and backend for real-time error tracking and telemetry.

## 📝 Maintenance Instructions
*   Whenever a component's internal `package.json` or `composer.json` is updated, the global `VERSION` file in the root directory must be bumped.
*   All component versions should remain perfectly synchronized (e.g., if Backend goes to 1.0.0, Frontend must also go to 1.0.0) to avoid compatibility drift.
