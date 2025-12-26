# Claude Instructions

> **⚠️ IMPORTANT:** The **MASTER PROTOCOL** is located in `docs/LLM_INSTRUCTIONS.md`. You MUST read that file first.

## 🧠 Claude-Specific Context
-   **Context Window**: You have a large context window. Use it to read multiple related files at once (`read_file` with multiple paths) to understand the full scope of a feature.
-   **Reasoning**: Show your work. Explain *why* you are making a change before you make it.
-   **Code Generation**: Prefer generating complete file contents over small patches when the file is small (< 200 lines) to avoid context drift.

## 📜 Critical Instructions
1.  **Versioning**: Read VERSION file. Increment it. Sync package.json. Update CHANGELOG.md.
2.  **Testing**: Always verify changes.
3.  **Documentation**: Keep PROJECT_STATUS.md up to date.

