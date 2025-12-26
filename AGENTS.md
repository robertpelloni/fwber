# AGENTS.md

> **⚠️ IMPORTANT:** The **MASTER PROTOCOL** is located in `docs/LLM_INSTRUCTIONS.md`. You MUST read that file first.

## 🧠 Agent-Specific Context
This file contains specific instructions for autonomous agents (e.g., AutoGPT, BabyAGI) operating within this workspace.

### 🔍 Research & Planning
-   When asked to research, use `runSubagent` with the `Plan` agent.
-   Always verify assumptions by reading the actual code, not just documentation.

### 🛠️ Execution
-   Follow the **SOP** defined in `docs/LLM_INSTRUCTIONS.md`.
-   Use the `run_in_terminal` tool for file system operations and testing.
-   Use `read_file` to gather context before editing.

### 📝 Documentation
-   Keep `PROJECT_STATUS.md` updated with your progress.
-   Log significant findings in `docs/research/`.

