# FWBER AGENT INSTRUCTIONS

> **See [docs/UNIVERSAL_LLM_INSTRUCTIONS.md](docs/UNIVERSAL_LLM_INSTRUCTIONS.md) for the Master Protocol.**

This file serves as a pointer to the single source of truth for all LLM agents. Please refer to the universal instructions for:
- Versioning Protocol
- Code Standards
- Project Structure
- Execution Workflow

**Current Version:** 0.3.34
**Status:** See `docs/PROJECT_STATUS.md`.

## 📜 Universal Protocols (MANDATORY)

### 1. Versioning & Changelog
*   **Single Source of Truth**: The `VERSION` file in the root directory.
*   **Protocol**:
    1.  Read `VERSION`.
    2.  Increment (Patch/Minor/Major).
    3.  Update `VERSION` file.
    4.  Update `CHANGELOG.md` with a new `## [Version] - YYYY-MM-DD` section.
    5.  Commit with message: `chore(release): bump version to [Version]`.
*   **Constraint**: Every session or significant logical unit of work **MUST** result in a version bump.

### 2. Documentation
*   **Project Status**: Always update `PROJECT_STATUS.md` to reflect current state.
*   **Dashboard**: maintain `docs/dashboard/PROJECT_STRUCTURE_DASHBOARD.md`.

