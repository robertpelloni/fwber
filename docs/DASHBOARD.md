# Project Dashboard & Submodule Registry

**Last Updated:** December 26, 2025
**Version:** v0.3.10

## 📂 Project Structure Overview

The FWBer project is a monorepo-style workspace containing the backend API, frontend application, and documentation.

```
fwber/
├── fwber-backend/       # Laravel 12 API (Submodule)
├── fwber-frontend/      # Next.js 14 Client (Submodule)
├── docs/                # Project Documentation
├── docker/              # Docker Configuration
└── scripts/             # Operational Scripts
```

## 📦 Submodule Registry

| Submodule | Path | Version (Tag/Commit) | Description |
| :--- | :--- | :--- | :--- |
| **Backend** | `fwber-backend/` | `HEAD` (Main) | Laravel API, MySQL, Redis, Mercure Publisher. |
| **Frontend** | `fwber-frontend/` | `HEAD` (Main) | Next.js App Router, Tailwind, PWA, Mercure Subscriber. |

### 📍 Submodule Locations & Details

#### 1. Backend (`fwber-backend/`)
-   **Location**: Root `/fwber-backend`
-   **Stack**: PHP 8.2, Laravel 12
-   **Key Config**: `.env`, `composer.json`
-   **Build**: `composer install`, `php artisan migrate`

#### 2. Frontend (`fwber-frontend/`)
-   **Location**: Root `/fwber-frontend`
-   **Stack**: Node.js 20, Next.js 14
-   **Key Config**: `.env.local`, `package.json`
-   **Build**: `npm install`, `npm run build`

## 🔄 Synchronization Status
-   **Remote**: Origin (GitHub)
-   **Branch**: `main`
-   **Sync State**: Up to date (as of Dec 26, 2025)

## 🛠 Operational Commands

### Update All Submodules
```bash
git submodule update --init --recursive
git submodule foreach git pull origin main
```

### Check Status
```bash
git status
git submodule status
```
