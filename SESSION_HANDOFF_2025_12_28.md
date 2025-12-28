# Session Handoff - 2025-12-28

## 📝 Summary
This session focused on formalizing the project's operational protocols and resolving a critical production crash.

1.  **Critical Fix**: Resolved a "Module factory not available" error in production by refactoring `instrumentation.ts` to use dynamic imports for `@sentry/nextjs`. This broke a circular dependency chain.
2.  **Protocol Enforcement**: Implemented a strict "Operational Charter" across all agent instruction files (`AGENTS.md`, `CLAUDE.md`, etc.).
3.  **Documentation**:
    *   Updated `docs/LLM_INSTRUCTIONS.md` as the Single Source of Truth.
    *   Updated `docs/PROJECT_STRUCTURE.md` to serve as the project dashboard.
4.  **Versioning**: Bumped version to **0.3.15** and updated `CHANGELOG.md`.

## 🛠️ Technical Decisions
*   **Dynamic Imports for Instrumentation**: Next.js instrumentation runs early in the boot process. Static imports of heavy libraries like Sentry, which might be imported elsewhere (like `lib/logger.ts`), can cause Webpack to fail resolving the module graph. Moving to `await import(...)` inside `register()` defers this loading.
*   **Universal Instructions**: To prevent agent drift, all model-specific instruction files now point to `docs/LLM_INSTRUCTIONS.md` as the master protocol.

## 📂 Modified Files
*   `fwber-frontend/instrumentation.ts` (Fix)
*   `docs/LLM_INSTRUCTIONS.md` (Protocol)
*   `AGENTS.md`, `CLAUDE.md`, `.github/copilot-instructions.md` (Protocol)
*   `docs/PROJECT_STRUCTURE.md` (Dashboard)
*   `CHANGELOG.md`
*   `VERSION`
*   `fwber-frontend/cypress/e2e/presence-stability.cy.js` (New Test)
*   `fwber-backend/tests/Feature/BulletinBoardBroadcastingTest.php` (New Test)

## ⏭️ Next Steps
1.  **Monitor Production**: Verify the "Module factory not available" error is gone.
2.  **Feature Implementation**: Resume work on the Roadmap.
3.  **Submodule Status**: Confirmed that `fwber-frontend` and `fwber-backend` are standard directories (no `.gitmodules`).


