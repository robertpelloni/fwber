# CLAUDE Guidelines

This repository uses a scoped, MVP-first approach. Please adopt a concise, plan-then-implement workflow with clear guardrails.

## Quick context
- Laravel 11 backend lives in `fwber-backend/`.
- Feature flags in `fwber-backend/config/features.php`, enforced via middleware alias `feature` (registered in `fwber-backend/bootstrap/app.php`). Disabled features should return 404 via middleware.
- OpenAPI is documented via L5-Swagger + swagger-php. Reusable schemas live inside `fwber-backend/app/Http/Controllers/Schemas.php` (file-level docblock). Endpoint annotations live in controllers.

## Working principles
- Stay in scope: Do not modify disabled features unless explicitly requested. Gate any non‑MVP routes you introduce with `->middleware('feature:<flag>')` and add a config key if needed.
- Minimize reads: Skim only relevant files/symbols—avoid full-file reads unless necessary.
- Plan first: Maintain a short todo and acceptance criteria before editing.
- Make small patches: Implement the smallest change that solves the problem.
- Test quickly:
  - Validate routes: `php artisan route:list`
  - Regenerate docs: `php artisan l5-swagger:generate`
- Document changes: Update or add annotations; ensure new controller paths are in `config/l5-swagger.php` under `annotations` if applicable.

## Do / Don’t
- Do keep `@OA\Schema` definitions consolidated in `Schemas.php`.
- Do keep non‑MVP behind feature flags and return 404 when disabled.
- Do avoid unrelated refactors or formatting changes.
- Don’t expose secrets; extend `.env.example` for new env variables.
- Don’t over-engineer—prefer straightforward, reversible changes.

## Useful references
- Scope and roadmap: `/PROJECT_ANALYSIS_AND_ROADMAP_2025-11-15.md`
- Workflow: `/docs/guides/WORKFLOW_GUIDE.md`
- Feature flags: `/fwber-backend/config/features.php`
- Swagger scan paths: `/fwber-backend/config/l5-swagger.php`

## When uncertain
Present options with trade-offs, prefer the least risky path that ships the MVP, and articulate how features will remain toggleable through flags.
