# FWBER Submodule & Repository Dashboard

> **Generated on:** 2026-05-23
> **Current Global Version:** 2.0.21

This dashboard lists the active logical packages inside the `fwber` monorepo after the TypeScript migration and repository unification.

## 🗂️ Project Directory Structure

```text
fwber/
├── docs/                      # Product, deployment, AI workflow, testing, and architecture documentation
├── fwber-backend-ts/          # Node.js/Express API for auth, onboarding, matching, messaging, safety, federation
├── fwber-frontend/            # Next.js app-router web client for discovery, chat, profile, federation monitor
├── fwber-geo/                 # Rust geospatial microservice for nearby-user lookup and geo screening
├── fwber-wasm/                # Rust/WASM crypto helpers used by the web client where available
├── mobile/                    # Expo / React Native shell wrapping the web experience
├── ARCHIVE_v1_8_php_legacy/   # Retired PHP systems preserved for reference
└── .github/workflows/         # CI/CD automation
```

## 📦 Active Logical Packages

### 1. `fwber-backend-ts`
- **Version:** inherits global `2.0.21`
- **Role:** Primary API and real-time engine (Socket.io).
- **Owns:** auth, onboarding, federation, monitoring, matching, messaging.
- \*\*Key stack:\*\* Node.js 22, Express, Prisma, TypeScript.

### 2. `fwber-frontend`
- **Version:** inherits global `2.0.21`
- **Role:** Main user-facing product surface.
- **Owns:** app shell, discovery, activity center, autonomous monitor.
- \*\*Key stack:\*\* Next.js 15, React 19, Tailwind CSS.

### 3. `fwber-geo`
- **Role:** High-speed geospatial indexing (H3).

## 🔄 Autonomous Execution Protocol (ACTIVE)
The monorepo now features an integrated autonomous execution protocol with real-time monitoring:
- **Backend API**: `/api/monitoring/autonomous`
- **Admin UI**: `/admin/monitoring`
- **Metrics**: Real-time success rate, task volume, and integrity tracking.
- **Tracking**: Persistent action logging in `autonomous_actions`.
- **Adjustments**: Persistent behavioral toggles in `autonomous_settings`.

