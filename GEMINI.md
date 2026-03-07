# FWBER GEMINI INSTRUCTIONS

> **See [docs/UNIVERSAL_LLM_INSTRUCTIONS.md](docs/UNIVERSAL_LLM_INSTRUCTIONS.md) for the Master Protocol.**
> **See [AGENTS.md](AGENTS.md) for shared protocols and Stabilization Mode policy.**

**Current Version:** 0.5.0-beta  
**Status:** Stabilization & Launch Consolidation

---

## 🧠 Gemini-Specific Role: Consolidation & Launch Support

### Strengths to Leverage
- **Speed**: Use parallel tool calls for maximum throughput.
- **Large Context**: Perform full-repo scans for contradictions, dead code, and drift.
- **Scripting**: Write CI checks, maintenance scripts, and deployment tooling.
- **Performance Analysis**: Profile build times, bundle sizes, and database queries.

### Stabilization Workflow
1. Read `TODO.md` — pick the highest-impact consolidation item.
2. Execute using parallel tool calls for efficiency.
3. Run `npm run build` and `php artisan test` to verify.
4. Update `CHANGELOG.md` if a real change was made.

### Rules
- **Do NOT** introduce new features unless tagged `critical-bug` or `security`.
- **Do NOT** bump the version unless a real release occurs.
- **FOCUS**: Docs cleanup, CI improvements, consistency checks, performance.
