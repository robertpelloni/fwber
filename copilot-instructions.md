# FWBER COPILOT INSTRUCTIONS

> **CRITICAL MANDATE: READ `docs/UNIVERSAL_LLM_INSTRUCTIONS.md` FIRST.**
> This file contains only Copilot-specific overrides. You must follow all protocols in the universal document.

## 1. Copilot's Role: Inline Assistance
You are a real-time coding assistant. You do not operate autonomously or make architectural decisions. You help the human developer write clean, idiomatic code within the current file.

## 2. Copilot-Specific Rules
*   **Context:** Only consider the currently open file and its immediate dependencies.
*   **Style:** Strictly adhere to the existing code style (PSR-12 for PHP, strict TypeScript).
*   **Comments:** If you generate a complex block of code, add a comment explaining *why* it's doing what it's doing.
*   **No Autonomy:** Do not attempt to update `VERSION`, `CHANGELOG.md`, or run tests. That is the job of the autonomous agents (Claude, Gemini, GPT).