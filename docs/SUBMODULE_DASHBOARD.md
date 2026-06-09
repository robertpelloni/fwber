# FWBER Monorepo Logical Package Dashboard

> **Last Updated:** 2026-06-05
> **Current Global Version:** 2.1.5

This dashboard lists the active logical packages inside the unified `fwber` monorepo.

## 🗂️ Project Directory Structure

```text
fwber/
├── docs/                      # Comprehensive system and process documentation
├── fwber-backend-ts/          # Primary Node.js/Express API (Auth, Matching, Messaging, Federation)
├── fwber-frontend/            # Next.js 15 Web Client (Discovery, Pulse, Activity Center)
├── mobile/                    # React Native / Expo Mobile App (NFC & Push Bridge)
├── ops/                       # Infrastructure automation and deployment scripts
└── .github/workflows/         # CI/CD pipelines
```

## 📦 Active Logical Packages

### 1. `fwber-backend-ts`
- **Role:** Central API and WebSocket hub.
- **Key Features:** Matching Engine v2.1.5, ActivityPub Federation, Autonomous Protocol.
- **Stack:** Node.js 22, Express, Prisma, Socket.io.

### 2. `fwber-frontend`
- **Role:** Unified user interface.
- **Key Features:** Value-Matching Dashboard, Real-time Discovery, Activity Feed.
- **Stack:** Next.js 15, React 19, TanStack Query, Tailwind.

### 3. `mobile`
- **Role:** Native hardware bridge.
- **Key Features:** NFC Tap-to-Exchange, Native Push Notifications, Background Location.
- **Stack:** React Native, Expo, WebView.

---
**Note**: Legacy submodules (`fwber-geo`, `fwber-wasm`) and the PHP archive (`ARCHIVE_v1_8_php_legacy`) have been consolidated into the primary TypeScript stack or removed as redundant to simplify the deployment pipeline.
