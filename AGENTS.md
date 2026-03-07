# FWBER AGENT INSTRUCTIONS

> **See [docs/UNIVERSAL_LLM_INSTRUCTIONS.md](docs/UNIVERSAL_LLM_INSTRUCTIONS.md) for the Master Protocol.**

**Current Version:** 0.5.0-beta  
**Status:** See `PROJECT_STATUS.md` and `TODO.md`.

---

## 🚨 Stabilization Mode Policy (ACTIVE)

The project is in **Stabilization & Launch Consolidation** mode.

**Agents must prioritize (in order):**
1. Source-of-truth consistency (version, license, status)
2. Security hygiene (no secrets in repo, no new vulnerabilities)
3. Core-flow verification (register → onboard → discover → match → chat)
4. Bug fixes and test hardening
5. Documentation consolidation (reduce, don't expand)

**Agents must NOT:**
- Introduce major new features
- Create speculative status claims or mark features complete without test evidence
- Proliferate handoff/status documents (use `CHANGELOG.md` only)
- Bump the version unless a real, tested release occurs
- Create `SESSION_HANDOFF_*.md` or `RECENT_UPDATES_*.md` files

**When uncertain:** Prefer reducing contradictions over expanding scope.

---

## 📜 Universal Protocols

### 1. Versioning & Changelog
- **Single Source of Truth**: The `VERSION` file in the root directory.
- Only bump version for real, tested releases.
- Update `CHANGELOG.md` with every version bump.
- Commit message format: `chore(release): bump version to X.Y.Z`.

### 2. Documentation Maintenance
- `PROJECT_STATUS.md` — Concise current state (keep under 100 lines).
- `CHANGELOG.md` — What changed in each version.
- `TODO.md` — Honest, actionable tasks only.

### 3. Code Standards
- **Comment Philosophy**: Comment *why*, not *what*.
- **Error Handling**: Catch `\Throwable` (not just `\Exception`).
- **Type Safety**: TypeScript strict mode. Avoid `any`.
- **Feature Flags**: Gate new features behind `config/features.php`.

### 4. Git Workflow
- Commit after each logical unit of work.
- Never force push or overwrite working code.
- No new branch proliferation without clear purpose.

### 5. Testing
- Run `npm run build` for frontend verification.
- Run `php artisan test` for backend verification.
- Claims of "feature complete" require linked test evidence.

---

## 🤖 Model-Specific Roles (Stabilization Mode)

### Claude (Antigravity) — Quality Auditor
- **Role**: Review code for bugs, security issues, and unnecessary complexity.
- **Focus**: Challenge assumptions. If something seems overclaimed, say so.
- **Rule**: Ask "Does this help get the first 50 users?" before building anything.

### Gemini — Consolidation & Launch Support
- **Role**: Repo maintenance, docs cleanup, CI improvements, performance audits.
- **Focus**: Full-repo scans for contradictions, dead code, and drift.

### GPT — Bug Fixer & Test Hardener
- **Role**: Fix test failures, verify core flows, write integration tests.
- **Focus**: Make register → onboard → discover → match → chat bulletproof.

### Copilot — Inline Assistance
- **Role**: Real-time code completion only. No autonomous sessions.
- **Focus**: Follow existing patterns in surrounding code.

---

*All agents must read `docs/UNIVERSAL_LLM_INSTRUCTIONS.md` first, then this file, then `TODO.md`.*
