# FWBER AGENT INSTRUCTIONS

> **See [docs/UNIVERSAL_LLM_INSTRUCTIONS.md](docs/UNIVERSAL_LLM_INSTRUCTIONS.md) for the Master Protocol.**

**Current Version:** 0.5.1-beta  
**Status:** See `PROJECT_STATUS.md` and `TODO.md`.

---

## 🚨 Autonomous Execution Policy (ACTIVE)

When instructed to "proceed" or "keep going", you are authorized and expected to operate in a continuous, autonomous loop:

1. **Analyze:** Deeply review `ROADMAP.md`, `TODO.md`, and `PROJECT_STATUS.md` to find missing or incomplete features.
2. **Implement:** Write robust, well-commented code. Ensure full UI representation (tooltips, labels, config).
3. **Verify:** Test your code. Fix any bugs.
4. **Version Bump:** Update the `VERSION` file and `CHANGELOG.md` for *every* completed feature build.
5. **Git Sync:** `git add .`, `git commit -m "feat: [description] (vX.Y.Z)"`, and `git push`.
6. **Loop:** Do not stop. Automatically pick the next feature and proceed.

**Agents must NOT:**
- Stop or pause for confirmation unless fundamentally blocked or specifically asked to stop.
- Implement half-features. If you build a backend API, you MUST build the frontend UI to consume it.
- Leave uncommented code if it requires explanation.

---

## 📜 Universal Protocols

### 1. Versioning & Changelog
- **Single Source of Truth**: The `VERSION` file in the root directory.
- Update `CHANGELOG.md` with every version bump.

### 2. Documentation Maintenance
- `PROJECT_STATUS.md` — Concise current state.
- `CHANGELOG.md` — What changed in each version.
- `TODO.md` — Honest, actionable tasks only.
- `HANDOFF.md` — Extreme detail of findings, actions, and next steps for the next AI agent.

### 3. Code Standards
- **Comment Philosophy**: Comment *why*, not *what*. Explain side-effects, optimizations, and non-working methods you tried.
- **Error Handling**: Catch `\Throwable` (not just `\Exception`).
- **Type Safety**: TypeScript strict mode. Avoid `any`.

---

## 🤖 Model-Specific Roles

### Claude (Antigravity) — Architecture & Refactoring Lead
- **Role**: Review code for bugs, architectural flaws, and complexity. Lead deep refactoring efforts (e.g., Event Sourcing migration).
- **Focus**: See `CLAUDE.md`.

### Gemini — Consolidation & Ecosystem Lead
- **Role**: Repo maintenance, cross-submodule synchronization, docs cleanup, CI improvements, performance audits.
- **Focus**: See `GEMINI.md`.

### GPT — Feature Implementation & Bug Fixer
- **Role**: Fix test failures, verify core flows, write integration tests, build out missing UI.
- **Focus**: See `GPT.md`.

### Copilot — Inline Assistance
- **Role**: Real-time code completion only. No autonomous sessions.
- **Focus**: Follow existing patterns in surrounding code.

---

*All agents must read `docs/UNIVERSAL_LLM_INSTRUCTIONS.md` first, then this file, then `TODO.md`.*