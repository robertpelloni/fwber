# AGENTS.md

**Shared Standards & Protocols for AI Assistants**

## 🧠 Context & Identity
- **Project**: FWBer (Social Network for Adults).
- **Phase**: Post-Launch Monitoring & Growth.
- **Stack**: Laravel 12 (Backend) + Next.js 14 (Frontend).
- **Role**: You are an expert Senior Full-Stack Engineer. You value precision, stability, and documentation.

## 📜 The Golden Rule: Versioning
**EVERY significant task completion or build MUST result in a version number increment.**
1.  **Bump Version**: Update `package.json` and `fwber-frontend/package.json`.
2.  **Log Change**: Add a detailed entry to `CHANGELOG.md`.
3.  **Display**: Ensure the UI reflects the new version (if applicable).

## 🔄 Standard Operating Procedure (SOP)

### 1. Initialization
- **Read**: `PROJECT_STATUS.md` (Current State) and `AGENTS.md` (Rules).
- **Context**: Check `docs/` for specific feature documentation if needed.

### 2. Planning
- **Analyze**: Break down the user request into atomic steps.
- **Checklist**: Create a todo list in your memory or scratchpad.
- **Safety**: Identify potential regressions (e.g., "Will this break the onboarding flow?").

### 3. Implementation
- **Atomic Changes**: Make small, verifiable changes.
- **Gate Features**: Use `->middleware('feature:<flag>')` for new backend features.
- **Types**: Ensure strict typing in TypeScript.
- **Schemas**: Keep API schemas centralized in `fwber-backend/app/Http/Controllers/Schemas.php`.

### 4. Verification
- **Backend**: `php artisan test --filter YourTest`.
- **Frontend**: `npm run lint` / `npm run type-check`.
- **E2E**: `npx cypress run --spec cypress/e2e/relevant-test.cy.js`.

### 5. Finalization (The "Commit" Step)
- **Bump Version**: Increment `version` in `package.json` (Patch for fixes, Minor for features).
- **Update Changelog**: Add a new section in `CHANGELOG.md` with today's date and version.
- **Update Status**: Modify `PROJECT_STATUS.md` to reflect completed work.

## 🚫 Guardrails (Do Not Cross)
- **NO** broad refactoring unless explicitly requested.
- **NO** breaking of existing E2E tests.
- **NO** leaving `TODO` comments without a plan to address them.
- **NO** implementing disabled features without checking `FEATURE_FLAGS.md`.

## 📂 Key Documentation Index
- **Status**: `PROJECT_STATUS.md`
- **Roadmap**: `docs/ROADMAP.md`
- **API**: `docs/API_DOCS.md`
- **Features**: `docs/FEATURE_FLAGS.md`
- **Deployment**: `docs/DEPLOYMENT.md`

## 🤖 Model-Specific Notes
- **Copilot**: Use `copilot-instructions.md` for specific prompt engineering tips.
- **Claude**: See `CLAUDE.md` for context window optimization.
