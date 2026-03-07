# FWBER COPILOT INSTRUCTIONS

> **See [docs/UNIVERSAL_LLM_INSTRUCTIONS.md](docs/UNIVERSAL_LLM_INSTRUCTIONS.md) for the Master Protocol.**
> **See [AGENTS.md](AGENTS.md) for shared protocols and Stabilization Mode policy.**

**Current Version:** 0.5.0-beta  
**Status:** Stabilization & Launch Consolidation

---

## 🧠 Copilot-Specific Role: Inline Assistance

### Guidelines
- Follow existing patterns in surrounding code.
- Use TypeScript strict types — avoid `any`.
- Prefer `useCallback` and `useMemo` for React hooks.
- Use `framer-motion` for animations.
- Gate new features behind `config/features.php` flags.
- Comment non-obvious design decisions.
- **Do NOT** suggest new features — focus on bug fixes and code quality.
