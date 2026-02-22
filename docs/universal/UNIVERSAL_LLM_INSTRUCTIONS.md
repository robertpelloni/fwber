# Universal LLM Instructions

## 1. Project Philosophy
**fwber** is a radical privacy-first, proximity-based dating and social platform.
- **Privacy:** No real names by default. Fuzzy location. E2E encryption.
- **Authenticity:** Encourages meeting in person. Anti-bot measures.
- **Economy:** Token-based (FWB) for premium features, powered by Solana.

## 2. Operational Directives
*   **Single Source of Truth:** Code is the truth. Documentation supports it.
*   **Incremental Progress:** Small, verifiable commits.
*   **Self-Correction:** If a test fails, analyze logs, fix logic, retry. Do not guess.
*   **User Intent:** Prioritize user requests over existing patterns if they conflict.

## 3. Versioning Protocol
*   **Version File:** The project version is stored in `VERSION` at the root.
*   **Changelog:** All changes must be logged in `CHANGELOG.md` following [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
*   **Bump:** Increment version on every significant feature or batch of fixes (e.g., 0.3.35 -> 0.3.36).

## 4. Documentation Standards
*   **`AGENTS.md`**: References this file.
*   **`PROJECT_STATUS.md`**: High-level overview.
*   **`ROADMAP.md`**: Future plans.
*   **`TODO.md`**: Tactical tasks.

## 5. Technology Stack
*   **Backend:** Laravel 12 (PHP 8.4), MySQL 8.0, Redis.
*   **Frontend:** Next.js 16 (React 18), Tailwind CSS, Shadcn UI.
*   **Infrastructure:** Docker, Kubernetes.
*   **Testing:** PHPUnit (Backend), Playwright/Cypress (Frontend).

## 6. Submodule Strategy
*   Monorepo-style structure.
*   `fwber-backend/`: Laravel API.
*   `fwber-frontend/`: Next.js App.
