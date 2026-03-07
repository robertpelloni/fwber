# Universal LLM Instructions — fwber

> **CRITICAL: This is the single source of truth for all AI agents working on fwber.**

## 1. Project Context

fwber is an open-source, privacy-first proximity dating platform. It combines AI-generated avatars with location-based discovery.

- **Stack:** Laravel 12 (PHP 8.4) + Next.js 16 (React 18) + MySQL
- **License:** MIT
- **Status:** Beta (0.5.0-beta) — Stabilization & Launch Consolidation
- **Hosting:** DreamHost Shared (current), Docker/Kubernetes ready

## 2. Stabilization Mode (ACTIVE)

The project is in **feature freeze**. All agents must prioritize:

1. **Source-of-truth consistency** — version, license, status docs must not contradict each other
2. **Security hygiene** — no secrets in repo, no new vulnerabilities
3. **Core-flow verification** — register → onboard → discover → match → chat
4. **Bug fixes and test hardening** — fix failures, write missing tests
5. **Documentation consolidation** — reduce docs, don't expand them

### Forbidden Actions
- ❌ Introducing new features (unless tagged `critical-bug` or `security`)
- ❌ Creating `SESSION_HANDOFF_*.md` or `RECENT_UPDATES_*.md` files
- ❌ Bumping the version without a real, tested release
- ❌ Marking features "complete" without test evidence
- ❌ Writing sycophantic status updates

### Required Actions
- ✅ Read `VERSION` file before writing any docs
- ✅ Run `npm run build` and/or `php artisan test` to verify changes
- ✅ Link to tests when claiming a feature works
- ✅ Update `CHANGELOG.md` when making real changes

## 3. Versioning Protocol

- **Single Source of Truth:** `VERSION` file in the project root.
- Only bump for real, tested releases.
- Sync `VERSION` across: `AGENTS.md`, `CLAUDE.md`, `GEMINI.md`, `GPT.md`, `copilot-instructions.md`.
- Commit format: `chore(release): bump version to X.Y.Z`.

## 4. Code Standards

- **PHP:** PSR-12, catch `\Throwable` not `\Exception`, type hints everywhere.
- **TypeScript:** Strict mode, no `any`, proper interfaces.
- **Comments:** Explain *why*, not *what*.
- **Features:** Gate behind `config/features.php` flags.
- **Real-time:** Use `useWebSocket` hook (not legacy Mercure hooks).
- **Animations:** Use `framer-motion`.

## 5. Testing

- Backend: `php artisan test` (PHPUnit)
- Frontend: `npm run build` (Next.js compilation check)
- E2E: Cypress tests in `cypress/e2e/`

## 6. Model-Specific Roles

| Agent | Role | Focus |
|-------|------|-------|
| Claude | Quality Auditor | Review, contradiction detection, architecture |
| Gemini | Consolidation Lead | Repo scans, CI improvements, docs cleanup |
| GPT | Bug Fixer | Test hardening, core flow verification |
| Copilot | Inline Only | Code completion, no autonomous sessions |

## 7. Key Files

| File | Purpose |
|------|---------|
| `VERSION` | Canonical version number |
| `PROJECT_STATUS.md` | Concise current state |
| `TODO.md` | Actionable task list |
| `CHANGELOG.md` | Version history |
| `AGENTS.md` | Agent protocols and stabilization policy |
| `docs/FEATURE_STATUS_MATRIX.md` | Feature maturity categorization |
