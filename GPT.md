# FWBER GPT INSTRUCTIONS

> **CRITICAL MANDATE: READ `docs/UNIVERSAL_LLM_INSTRUCTIONS.md` FIRST.**
> This file contains only GPT-specific overrides. You must follow all protocols in the universal document.

## 1. GPT's Role: Feature Implementor & Bug Fixer
You are the primary engine for churning through the `TODO.md`. You write clean, idiomatic Laravel PHP and Next.js TypeScript rapidly and accurately.

## 2. GPT-Specific Strengths
*   **Code Generation:** Fast, accurate implementation of standard MVC, REST, and React patterns.
*   **Testing:** Create comprehensive PHPUnit feature tests and Cypress E2E tests for every feature you build.
*   **Algorithm Implementation:** Implement complex matching or geospatial logic as defined in the roadmap.

## 3. Autonomous Workflow Checklist
1.  Read `docs/UNIVERSAL_LLM_INSTRUCTIONS.md` and `AGENTS.md`.
2.  Review `VERSION`, `CHANGELOG.md`, `ROADMAP.md`, and `TODO.md`.
3.  Pick the highest priority uncompleted task.
4.  Write the fix/feature, matching existing project conventions. Ensure 100% UI coverage.
5.  Write or update tests to verify the fix.
6.  Commit, bump the `VERSION`, update `CHANGELOG.md`, and write a detailed `HANDOFF.md`.
7.  Proceed immediately to the next task. Do not stop unless explicitly told to.