# Project Structure & Architecture

**Version:** [Read from VERSION file]
**Last Updated:** December 25, 2025

## 📂 Directory Layout

### Root (`/`)
*   `VERSION`: Single source of truth for project version.
*   `AGENTS.md`, `CLAUDE.md`, etc.: AI Assistant guidelines.
*   `CHANGELOG.md`: Version history.
*   `PROJECT_STATUS.md`: High-level status report.
*   `deploy.sh`: Master deployment script.
*   `docker-compose.yml`: Production orchestration.
*   `docker-compose.dev.yml`: Development orchestration.

### Backend (`/fwber-backend`)
*   **Framework:** Laravel 12 (PHP 8.2+)
*   **Role:** API, WebSocket Server (Pusher/Reverb), Queue Worker, Scheduler.
*   **Key Directories:**
    *   `app/Http/Controllers`: API Logic.
    *   `routes/api.php`: API Endpoints.
    *   `config/`: Configuration (Features, Mercure, Services).
    *   `tests/`: PHPUnit Feature and Unit tests.

### Frontend (`/fwber-frontend`)
*   **Framework:** Next.js 14 (React 18, TypeScript)
*   **Role:** User Interface, PWA, Admin Dashboard.
*   **Key Directories:**
    *   `app/`: App Router pages.
    *   `components/`: Reusable UI components.
    *   `lib/`: Utilities, Hooks, Contexts.
    *   `cypress/`: End-to-End Tests.

### Documentation (`/docs`)
*   `LLM_INSTRUCTIONS.md`: Master protocol for AI agents.
*   `ROADMAP.md`: Feature roadmap.
*   `API_DOCS.md`: API Reference.
*   `DEPLOYMENT.md`: Deployment guide.

## 🧩 Submodules / Components

| Component | Location | Version Source | Description |
| :--- | :--- | :--- | :--- |
| **Root** | `/` | `VERSION` | Orchestration & Docs |
| **Backend** | `/fwber-backend` | `fwber-backend/package.json` | Laravel API |
| **Frontend** | `/fwber-frontend` | `fwber-frontend/package.json` | Next.js App |

*(Note: This project uses a Monorepo structure. `fwber-backend` and `fwber-frontend` are directories, not git submodules.)*
