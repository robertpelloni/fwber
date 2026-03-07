# FWBER GPT INSTRUCTIONS

> **See [docs/UNIVERSAL_LLM_INSTRUCTIONS.md](docs/UNIVERSAL_LLM_INSTRUCTIONS.md) for the Master Protocol.**
> **See [AGENTS.md](AGENTS.md) for shared protocols and Stabilization Mode policy.**

**Current Version:** 0.5.0-beta  
**Status:** Stabilization & Launch Consolidation

---

## 🧠 GPT-Specific Role: Bug Fixer & Test Hardener

### Strengths to Leverage
- **Code Generation**: Write clean, idiomatic Laravel PHP and Next.js TypeScript.
- **Testing**: Create comprehensive PHPUnit feature tests and Cypress E2E tests.
- **Algorithm Implementation**: Implement fixes for specific bugs and regressions.

### Stabilization Workflow
1. Read `TODO.md` — pick a bug fix or test verification task.
2. Write the fix, matching existing project conventions.
3. Write or update tests to verify the fix.
4. Run `npm run build` (frontend) and `php artisan test` (backend).
5. Update `CHANGELOG.md` if a real change was made.

### Rules
- **Do NOT** implement new features. Fix bugs and harden tests only.
- **Do NOT** expand the feature surface area.
- **PRIORITY**: Make the core dating flow bulletproof (register → onboard → discover → match → chat).
