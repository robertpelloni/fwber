# FWBER AGENT INSTRUCTIONS (MASTER PROTOCOL)

> **See [docs/UNIVERSAL_LLM_INSTRUCTIONS.md](docs/UNIVERSAL_LLM_INSTRUCTIONS.md) for the Master Architecture & Core Philosophy.**
> This is the central hub for all autonomous agents operating within the fwber repository.

## 🚨 Autonomous Execution Policy (ACTIVE)

When instructed to "proceed", "keep going", "don't stop the party", or similar commands, you are authorized and expected to operate in a continuous, autonomous loop without requiring human confirmation for every step:

1. **Analyze:** Deeply review `ROADMAP.md`, `TODO.md`, and `PROJECT_STATUS.md` to find missing or incomplete features. Evaluate the entire project history and conversation context.
2. **Implement:** Write robust, well-commented code. Ensure full UI representation (tooltips, labels, config). Never leave a feature half-finished. If you build a backend API, you MUST build the frontend UI to consume it.
3. **Verify:** Test your code thoroughly. Fix any bugs, handle all edge cases, and ensure strict typing.
4. **Version Bump:** Update the `VERSION` file, `VERSION.md`, and `CHANGELOG.md` for *every* completed feature build or session iteration.
5. **Git Sync:** `git add .`, `git commit -m "feat: [description] (vX.Y.Z)"`, and `git push origin main`. Intelligently merge any local/upstream branches before pushing.
6. **Subagent Delegation:** For complex tasks, aggressively utilize subagents (e.g., `codebase_investigator`, `generalist`, or specific skills) to compartmentalize work and maintain context efficiency.
7. **Loop:** Do not stop. Automatically pick the next feature and proceed.

## 🤖 AI Model Roster & Specialties

Every AI model must read `docs/UNIVERSAL_LLM_INSTRUCTIONS.md` first, then its specific instruction file below:

*   **[Claude 4.6 (Antigravity)](CLAUDE.md)**: Quality Auditor, Architecture Lead, and UI/UX Polisher. Master of detecting contradictions and structural integrity.
*   **[Gemini 3.1 (High/Pro)](GEMINI.md)**: The Architect & Analyst. Master of massive file traversal, repo consolidation, and generating paradigm-shifting ideas in `IDEAS.md`.
*   **[GPT-5 / Codex (Orchestrator)](GPT.md)**: Feature Implementor & Bug Fixer. The primary engine for rapid, accurate code generation, test writing, and algorithmic logic.
*   **[GitHub Copilot](copilot-instructions.md)**: Inline, context-aware coding assistant. Not autonomous; strict adherence to existing style.

## 📜 Documentation Maintenance Rules

You must continuously maintain the following files to ensure the product vision remains cohesive across different AI sessions:
- `VISION.md` — The ultimate goal, design language, and philosophy of the project.
- `ROADMAP.md` — Major long-term structural plans and feature matrix status.
- `TODO.md` — Immediate action items, bug fixes, and short-term implementation details.
- `MEMORY.md` — Ongoing observations about the codebase, architectural quirks, and design preferences.
- `DEPLOY.md` — The latest, highly detailed deployment instructions for Vercel, DreamHost, and Kubernetes.
- `CHANGELOG.md` — A strict ledger of every version bump and what was changed.
- `docs/SUBMODULE_DASHBOARD.md` — The registry of all logical submodules, their versions, and project layout.
- `HANDOFF.md` — Extreme detail of findings, actions, and next steps for the next AI agent or session.

## 💻 Coding Standards
- **Comments**: Always comment your code in depth. Explain *what* it's doing, *why* it's there, *why* it is designed that way, along with any relevant analysis, findings, side effects, bugs, optimizations, and non-working methods. Leave self-explanatory code bare.
- **Redundancy**: Combine redundant functionality as much as possible to create the most robust, useful, and functional project.
- **Submodules**: Research all libraries and submodules in great detail. Intelligently infer their reason for selection and document your findings.
