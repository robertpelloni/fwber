# Universal LLM Instructions — fwber

> **CRITICAL: This is the single source of truth for all AI agents working on fwber.**
> All agent-specific files (`CLAUDE.md`, `GEMINI.md`, `GPT.md`, `copilot-instructions.md`, `AGENTS.md`) MUST reference this file first.

## 1. Project Context & Vision

fwber is an open-source, privacy-first proximity dating platform. It combines AI-generated avatars with location-based discovery. The core loop is: Register -> Onboard -> Discover -> Match -> Chat.
For a deep dive into the project's philosophy, refer to `VISION.md`.

- **Stack:** Laravel 12 (PHP 8.4) + Next.js 16 (React 18) + MySQL + Rust (Geo-Screener)
- **License:** MIT
- **Hosting:** DreamHost Shared (current), Docker/Kubernetes ready

## 2. Autonomous Execution Protocol (ACTIVE)

When instructed to "proceed," "keep going," or "do not stop," you must adhere to the following autonomous execution loop:

1. **Deep Analysis:** Reanalyze the entire project, conversation logs, and all archived documentation in extreme detail before making a move.
2. **Feature Selection:** Identify missing, incomplete, or unpolished features (backend without frontend representation, etc.) from `ROADMAP.md` or `TODO.md`.
3. **Execution & Implementation:** Implement the feature thoroughly. Ensure it is robust, well-documented, and fully represented in the UI (labels, tooltips, options).
4. **Git Operations:** 
   - Merge any local feature branches into `main`, intelligently solving conflicts without losing progress.
   - Update all submodules and merge upstream changes.
   - **Commit and Push:** After completing a feature, perform a `git add .`, `git commit -m "..."`, and `git push` (if applicable).
5. **Version Bumping:** **EVERY BUILD OR COMPLETED FEATURE MUST HAVE A NEW VERSION NUMBER.** Update the `VERSION` file and the `CHANGELOG.md`. Reference the version bump in your commit message.
6. **Documentation Sync:** Immediately update `ROADMAP.md`, `TODO.md`, `PROJECT_STATUS.md`, `HANDOFF.md`, and `docs/SUBMODULE_DASHBOARD.md` to reflect the new reality. Clearly distinguish between what is done and what remains.
7. **Continuous Loop:** Do not stop. Proceed to the next missing feature autonomously. 

## 3. Communication & Memory Protocol

- **Extreme Detail:** Document input information, your findings, and your implementation steps in comprehensive, full extreme detail in `HANDOFF.md`.
- **Ask for Clarification:** If a goal or direction is uncertain, ask the user to clarify and reword it to develop a clearer description of the ultimate vision.
- **Memory Management:** Maintain `MEMORY.md` with ongoing observations about the codebase, architectural quirks, and design preferences. Use your system memory tools at the start and end of every session.
- **Commenting Code:** Always comment your code in depth. Explain *what* it is doing, *why* it is there, *why* it is designed that way, along with any analysis, side effects, bugs, optimizations, or alternative methods. If existing code lacks comments and needs them, add them. Do not add redundant comments to self-explanatory code.

## 4. Versioning & Changelog Protocol

- **Single Source of Truth:** `VERSION` file in the project root. This is the ONLY place the hardcoded version should exist.
- **Update Frequency:** Update the version number upon every build or feature completion.
- **Changelog:** Maintain a detailed `CHANGELOG.md`. Make sure all version numbers referenced anywhere synchronize with `CHANGELOG.md`.
- **UI Visibility:** The application UI should prominently display the current version by reading it dynamically (e.g., from an environment variable injected at build time, sourced from the `VERSION` file).

## 5. Submodules & Dependencies

- Research all libraries, submodules, and referenced projects in great detail. Intelligently infer their reason for selection.
- Document your findings in `docs/SUBMODULE_DASHBOARD.md`. This dashboard must list all submodules, their versions, dates, build numbers, and a clear explanation of the project directory structure.
- If a referenced project is crucial, consider adding it as a git submodule and documenting its functionality globally.

## 6. Code Standards & Testing

- **Backend (PHP):** PSR-12, catch `\Throwable`, strict typing.
- **Frontend (TypeScript):** Strict mode, no `any`, proper interfaces, `framer-motion` for animations.
- **Testing:** Test all functions meticulously. `php artisan test` for backend, Cypress for E2E. Check for bugs, unfinished code, or unpolished UI continuously.
- **Refactoring:** If code can be more robust or elegant, refactor it and document the change.

## 7. Model-Specific Role Overrides

*See individual files (`CLAUDE.md`, `GEMINI.md`, `GPT.md`) for proprietary instructions specific to those models. They must read this universal file first.*