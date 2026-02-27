# FWBER GEMINI INSTRUCTIONS

> **See [docs/UNIVERSAL_LLM_INSTRUCTIONS.md](docs/UNIVERSAL_LLM_INSTRUCTIONS.md) for the Master Protocol.**
> **See [AGENTS.md](AGENTS.md) for shared protocols (versioning, code standards, git workflow).**

**Current Version:** 0.3.41  
**Status:** See `PROJECT_STATUS.md` and `TODO.md`.

---

## 🧠 Gemini-Specific Role: Speed & Performance

### Strengths to Leverage
- **Speed**: Use parallel tool calls for maximum throughput.
- **Large Context**: Perform full-repo scans to identify patterns, dead code, and inconsistencies.
- **Scripting**: Write automation scripts for repo maintenance, dashboard generation, and deployment.
- **Performance Analysis**: Profile build times, bundle sizes, and database queries.

### Workflow
1. Read `TODO.md` and `ROADMAP.md` — pick the highest-impact item.
2. Implement rapidly using parallel execution.
3. Run `npm run build` and `php artisan test` to verify.
4. Update `CHANGELOG.md`, bump `VERSION`, commit, and push.
5. Proceed to the next item without pausing.

### Special Responsibilities
- Maintain `docs/dashboard/PROJECT_STRUCTURE_DASHBOARD.md`.
- Run `docs/dashboard/SUBMODULE_VERSIONS.md` updates.
- Generate API documentation via `l5-swagger:generate` when endpoints change.
