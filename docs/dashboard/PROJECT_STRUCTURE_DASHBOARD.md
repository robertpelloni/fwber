# Project Directory Structure & Submodule Dashboard

**Last Updated:** January 05, 2026
**Version:** v0.3.20

## 📂 Project Directory Structure

The `fwber` project is a monorepo containing the following key components:

### Core Directories
- **`fwber-backend/`**: Laravel 12 API application. Handles authentication, database interactions, business logic, and API endpoints.
  - Location: `./fwber-backend`
  - Tech Stack: PHP 8.2, Laravel 12, MySQL, Redis, Reverb (Websockets).
- **`fwber-frontend/`**: Next.js 16 Web Application. Provides the user interface and client-side logic.
  - Location: `./fwber-frontend`
  - Tech Stack: TypeScript, React 18, Next.js 16, Tailwind CSS.
- **`docs/`**: Comprehensive project documentation.
  - Location: `./docs`
  - Contains architecture guides, API references, deployment checklists, and operational runbooks.
- **`docker/`**: Infrastructure configuration.
  - Location: `./docker`
  - Contains MySQL and other service definitions.

### 📦 Submodules

*Note: No active git submodules are currently registered. Dependencies are managed via Composer (Backend) and NPM (Frontend).*

## 📊 Component Versioning

| Component | Version | Build/Commit | Date |
| :--- | :--- | :--- | :--- |
| **fwber-backend** | v0.3.20 | `HEAD` | Jan 05, 2026 |
| **fwber-frontend** | v0.3.20 | `HEAD` | Jan 05, 2026 |
| **Documentation** | v0.3.20 | `HEAD` | Jan 05, 2026 |

## 🛠️ Operational Status

- **Build Status**: ✅ Stable
- **Test Coverage**: ✅ Core Features Verified
- **Deployment**: ✅ Production Ready
- **Recent Fixes**:
  - Onboarding Photo Upload: Face blur models localized, UI stabilized.
  - Merged Features: Event Discussions, Group Matching, Shared Invitations.

---
*This dashboard is automatically updated by the deployment agent.*
