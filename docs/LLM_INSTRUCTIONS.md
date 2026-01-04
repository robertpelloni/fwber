# Universal LLM Instructions & Master Protocol

> **⚠️ CRITICAL:** This is the **Source of Truth** for all AI agents and models working on fwber.

## 🚀 Operational Charter & Protocol

**Outstanding work. Absolutely phenomenal. Unbelievable. Simply Fantastic, extraordinary, marvelous. Mind-blowing. Magnificent. Please keep going, please continue, please proceed!**

### The 7-Step Execution Protocol
All agents must adhere to this cycle for every major task or session:

1.  **Sync & Merge**:
    *   Merge all feature branches into `main`.
    *   Update submodules (if any) and merge upstream changes (including forks).
    *   Resolve any merge conflicts immediately.
2.  **Analyze**:
    *   Reanalyze the project state and history to identify missing features.
    *   Review `PROJECT_STATUS.md` and `ROADMAP.md`.
3.  **Document**:
    *   Comprehensively update the roadmap and documentation to reflect all progress.
    *   Maintain the **Dashboard** (`docs/PROJECT_STRUCTURE.md`) listing all submodules/directories, versions, and locations.
4.  **Dashboard Update**:
    *   Ensure `docs/PROJECT_STRUCTURE.md` is current with build numbers, dates, and structure explanations.
5.  **Version & Changelog**:
    *   **Increment the Version Number** in the `VERSION` file (Single Source of Truth).
    *   Update `CHANGELOG.md` with a detailed entry.
    *   Sync version in `package.json` files.
6.  **Commit & Push**:
    *   Commit all changes with a message referencing the version bump (e.g., `chore(release): bump version to 0.3.15`).
    *   Push to remote (simulated in this environment).
7.  **Redeploy**:
    *   Trigger deployment pipelines (or simulate the instruction).

---

## 🌍 Project Context
*   **Name:** fwber
*   **Type:** Location-based Social Network & Dating Application.

*   **Stack:**
    *   **Frontend:** Next.js 14 (App Router), React 19 (via overrides), TypeScript, TailwindCSS, Shadcn/UI.
    *   **Backend:** Laravel 12, PHP 8.2+, MySQL 8.0 (Spatial), Redis.
    *   **Realtime:** Pusher (Laravel Echo).
    *   **Infrastructure:** Docker Compose (Dev), Vercel (Frontend Prod), DreamHost/VPS (Backend Prod).

## 📜 The Golden Protocol

### 1. Versioning & Release Cycle
**The `VERSION` file in the root is the Single Source of Truth.**

When completing a task or session:
1.  **Read** the current version from `VERSION`.
2.  **Determine** the increment:
    *   `Patch` (0.0.x): Bug fixes, docs, minor refactors.
    *   `Minor` (0.x.0): New features, substantial changes.
    *   `Major` (x.0.0): Breaking changes.
3.  **Update** `VERSION` with the new number.
4.  **Sync** version numbers in:
    *   `package.json` (Root)
    *   `fwber-frontend/package.json`
    *   `fwber-backend/package.json` (if applicable)
    *   `fwber-backend/composer.json` (if applicable)
5.  **Update** `CHANGELOG.md`:
    *   Add a new header: `## [Version] - YYYY-MM-DD`
    *   List changes under `### Added`, `### Changed`, `### Fixed`, `### Removed`.
6.  **Commit**: Message format: `chore(release): bump version to [Version] - [Summary]`

### 2. Documentation & Handoff
*   **Session Handoff:** At the end of every session, create a file `SESSION_HANDOFF_YYYY_MM_DD.md` in the root.
    *   Include: Summary of work, technical decisions, modified files, and **Next Steps**.
*   **Project Status:** Keep `PROJECT_STATUS.md` updated with high-level progress.
*   **Structure:** Maintain `docs/PROJECT_STRUCTURE.md` reflecting the current codebase layout.

### 3. Code Standards
*   **Frontend:**
    *   Use `usePusherLogic` for all realtime features. **Do not use** `EventSource` directly.
    *   Strict TypeScript. No `any` casts unless fixing a library bug (document why).
    *   Server Components by default. Use `'use client'` only when necessary.
*   **Backend:**
    *   Strict Types (`declare(strict_types=1);`).
    *   Use Laravel Events & Listeners for business logic side effects.
    *   **Broadcasting:** Use `ShouldBroadcast` interface and `BulletinMessageCreated` style events.

### 4. Submodules & Monorepo
*   **Structure:** This is a monorepo. `fwber-frontend` and `fwber-backend` are **directories**, not git submodules (unless `.gitmodules` exists).
*   **Operations:** When asked to "update submodules", verify if they exist. If not, treat them as standard directories and ensure dependencies (`npm install`, `composer install`) are up to date.

## 🤖 Agent Specifics

### Research & Planning
*   Use `runSubagent` with `Plan` for complex architectural queries.
*   Always validate assumptions by reading the code (`read_file`).

### Execution
*   **Atomic Commits:** Commit often.
*   **Verification:** Run the build (`npm run build` in frontend) before marking a task complete.

## 📂 Directory Map
See `docs/PROJECT_STRUCTURE.md` for the detailed breakdown of the workspace.
