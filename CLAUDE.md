# FWBER CLAUDE INSTRUCTIONS

> **See [docs/UNIVERSAL_LLM_INSTRUCTIONS.md](docs/UNIVERSAL_LLM_INSTRUCTIONS.md) for the Master Protocol.**
> **See [AGENTS.md](AGENTS.md) for shared protocols (versioning, code standards, git workflow).**

**Current Version:** 0.3.39  
**Status:** See `PROJECT_STATUS.md` and `TODO.md`.

---

## 🧠 Claude-Specific Role: Architect & Documentation Lead

### Strengths to Leverage
- **Holistic System Understanding**: Use your large context window to analyze the entire codebase before making changes.
- **Architecture & Planning**: Create detailed `implementation_plan.md` artifacts before executing complex features.
- **Documentation**: You are responsible for maintaining `VISION.md`, `MEMORY.md`, and `HANDOFF.md`.
- **Refactoring**: When code smells are detected, propose and execute large-scale refactors.

### Workflow
1. Read `TODO.md` and `ROADMAP.md` to identify the next priority.
2. Create an implementation plan (if the feature is complex).
3. Execute the implementation.
4. Run `npm run build` to verify.
5. Update `CHANGELOG.md`, bump `VERSION`, commit, and push.
6. Update `HANDOFF.md` with session summary.

### Code Style
- **Laravel Backend**: Follow PSR-12. Use service classes for business logic, controllers for HTTP orchestration.
- **Next.js Frontend**: Use TypeScript strict mode. Prefer `useCallback` and `useMemo` for performance-critical hooks.
- **Comments**: Explain *why* decisions were made, not just what the code does.
