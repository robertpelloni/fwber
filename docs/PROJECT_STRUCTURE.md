# Project Structure & Dashboard

**Last Updated:** 2025-12-28
**Current Version:** 0.3.15 (See `VERSION` file)

## 📂 Repository Layout

This is a **Monorepo** structure containing both the frontend and backend applications.

```
fwber/
├── VERSION                 # Single Source of Truth for Project Version
├── CHANGELOG.md            # Detailed history of changes
├── docs/                   # Documentation & Protocols
│   ├── LLM_INSTRUCTIONS.md # Master Protocol for AI Agents
│   ├── PROJECT_STRUCTURE.md# This file
│   └── ...
├── fwber-frontend/         # Next.js Application (Directory)
│   ├── package.json        # Frontend Dependencies (v0.3.15)
│   ├── app/                # App Router Pages & Layouts
│   ├── lib/                # Shared Utilities & Hooks
│   └── ...
├── fwber-backend/          # Laravel Application (Directory)
│   ├── composer.json       # Backend Dependencies
│   ├── app/                # Core Business Logic
│   ├── routes/             # API Definitions
│   └── ...
└── docker-compose.yml      # Orchestration for Dev Environment
```

## 📦 Sub-Projects (Directories)

### Frontend (`fwber-frontend`)
*   **Type:** Next.js 14 Application
*   **Language:** TypeScript
*   **State:** Active Development
*   **Version:** 0.3.15 (Synced to Root Version)
*   **Key Tech:** React 19, Tailwind, Pusher-JS, Shadcn/UI.

### Backend (`fwber-backend`)
*   **Type:** Laravel 12 API
*   **Language:** PHP 8.2+
*   **State:** Active Development
*   **Key Tech:** MySQL 8 (Spatial), Redis, Laravel Echo Server (Pusher).

## 🔄 Versioning Strategy
The root `VERSION` file dictates the release version.
*   **Frontend:** `package.json` version should track the root version.
*   **Backend:** Version is generally tracked via Git Tags, but `config/app.php` or `composer.json` can be updated if configured.

## 🛠️ Infrastructure
*   **Docker:** `docker-compose.yml` spins up Frontend, Backend, MySQL, Redis, and Queue Workers.
*   **Production:**
    *   Frontend -> Vercel
    *   Backend -> VPS (DreamHost) via `deploy.sh`
