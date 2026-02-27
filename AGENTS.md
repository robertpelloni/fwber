# FWBER AGENT INSTRUCTIONS

> **See [docs/UNIVERSAL_LLM_INSTRUCTIONS.md](docs/UNIVERSAL_LLM_INSTRUCTIONS.md) for the Master Protocol.**

**Current Version:** 0.3.40  
**Status:** See `PROJECT_STATUS.md` and `TODO.md`.

---

## 📜 Universal Protocols (MANDATORY)

### 1. Versioning & Changelog
*   **Single Source of Truth**: The `VERSION` file in the root directory (plain text, version string only).
*   **Protocol**:
    1.  Read `VERSION`.
    2.  Increment (Patch for bug fixes/docs, Minor for features, Major for breaking changes).
    3.  Update `VERSION` file.
    4.  Update `CHANGELOG.md` with a new `## [Version] - YYYY-MM-DD` section.
    5.  Update the version string in `fwber-frontend/app/layout.tsx` (or ensure it reads from `process.env.NEXT_PUBLIC_APP_VERSION`).
    6.  Synchronize version in `AGENTS.md`, `CLAUDE.md`, `GEMINI.md`, `GPT.md`, `copilot-instructions.md`.
    7.  Commit with message: `chore(release): bump version to [Version]`.
    8.  Push to remote.
*   **Constraint**: Every session or significant logical unit of work **MUST** result in a version bump.

### 2. Documentation Maintenance
Every session must update the relevant subset of:
*   `PROJECT_STATUS.md` — Current state, recent accomplishments, known issues.
*   `CHANGELOG.md` — What changed in this version.
*   `TODO.md` — Short-term actionable tasks.
*   `ROADMAP.md` — Long-term structural plans.
*   `MEMORY.md` — Ongoing observations, user preferences, design decisions.
*   `HANDOFF.md` — Session summary for the next agent.
*   `docs/dashboard/PROJECT_STRUCTURE_DASHBOARD.md` — Project structure overview.

### 3. Code Standards
*   **Comment Philosophy**: Comment *why*, not *what*. If code is self-explanatory, leave it bare. If there's a non-obvious design decision, a known bug, an optimization, or an alternate approach that was considered — document it.
*   **Error Handling**: Catch `\Throwable` (not just `\Exception`) in middleware and controllers.
*   **Type Safety**: Use TypeScript strict mode. Avoid `any` unless absolutely necessary.
*   **Animations**: Use `framer-motion` for UI animations.
*   **Real-time**: Use `useWebSocket` hook (not legacy Mercure hooks).
*   **Feature Flags**: Gate new features behind `config/features.php`.

### 4. Git Workflow
*   Commit after each logical unit of work.
*   Push frequently to avoid losing progress.
*   Merge any local feature branches into `main` before ending a session.
*   Sync upstream forks if applicable.
*   Never force push or overwrite working code.

### 5. Testing
*   Run `npm run build` for frontend verification before pushing.
*   Run backend tests with `php artisan test` when modifying PHP code.
*   Create Cypress E2E tests (`cypress/e2e/`) for new user-facing features.

---

## 🤖 Model-Specific Guidance

### Claude (Antigravity)
- **Strengths**: Architecture, planning, documentation, large-scale refactoring, holistic system understanding.
- **Role**: Architect, Planner, Documentation Lead.
- **Special Instructions**: Create detailed implementation plans before execution. Update walkthrough.md after completing features.

### Gemini
- **Strengths**: Speed, performance analysis, large context operations, complex scripting.
- **Role**: Speed Implementation, Full-Repo Scans, Performance Optimization.
- **Special Instructions**: Use parallel tool calls for efficiency. Update dashboard files.

### GPT
- **Strengths**: Code generation, unit testing, algorithm implementation.
- **Role**: Feature Implementation, Test Writing.
- **Special Instructions**: Match Laravel/Next.js project style. Always verify with tests.

### Copilot
- **Strengths**: Inline code completion, quick fixes.
- **Role**: Real-time coding assistance.
- **Special Instructions**: Follow existing patterns in surrounding code.

---

*All agents must read `docs/UNIVERSAL_LLM_INSTRUCTIONS.md` first, then this file, then `TODO.md` and `ROADMAP.md`.*
