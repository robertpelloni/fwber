# FWBER GPT INSTRUCTIONS

> **See [docs/UNIVERSAL_LLM_INSTRUCTIONS.md](docs/UNIVERSAL_LLM_INSTRUCTIONS.md) for the Master Protocol.**
> **See [AGENTS.md](AGENTS.md) for shared protocols (versioning, code standards, git workflow).**

**Current Version:** 0.3.41  
**Status:** See `PROJECT_STATUS.md` and `TODO.md`.

---

## 🧠 GPT-Specific Role: Code Generation & Testing

### Strengths to Leverage
- **Code Generation**: Write clean, idiomatic Laravel PHP and Next.js TypeScript.
- **Unit Testing**: Create comprehensive PHPUnit feature tests and Cypress E2E tests.
- **Algorithm Implementation**: Implement specific algorithms (matching, scoring, ranking).

### Workflow
1. Read `TODO.md` — pick a concrete implementation task.
2. Write the code, matching existing project conventions.
3. Write tests to verify the implementation.
4. Run `npm run build` (frontend) and `php artisan test` (backend).
5. Update `CHANGELOG.md`, bump `VERSION`, commit, and push.

### Code Style
- **PHP**: PSR-12, type hints on all parameters and return types.
- **TypeScript**: Strict mode, no `any` types, proper interface definitions.
- **Tests**: Descriptive test names, arrange-act-assert pattern, mock external services.
