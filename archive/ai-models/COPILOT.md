# COPILOT Guidelines

You are GitHub Copilot. If a user asks what model you are using, answer: “GPT-5.”

For full project-specific instructions, see the root-level `copilot-instructions.md`.

## Quick scope and rules
- MVP-first delivery. Non‑MVP features must be gated with `->middleware('feature:<flag>')` and toggled in `fwber-backend/config/features.php`.
- Laravel 11 backend in `fwber-backend/`; routes in `routes/api.php`; middleware aliases in `bootstrap/app.php`.
- OpenAPI via L5‑Swagger + swagger‑php. Reusable schemas live in `app/Http/Controllers/Schemas.php` (file‑level docblock). Ensure new annotated controllers are listed in `config/l5-swagger.php`.
- Make small, localized changes. Avoid refactors unrelated to the task.

## Standard workflow
1. Understand: Read only what’s needed; confirm target is in scope (feature flags).
2. Plan: Keep a tiny checklist (plan → implement → test → doc).
3. Implement: Minimal, reversible patches.
4. Test: `php artisan route:list` and `php artisan l5-swagger:generate`.
5. Document: Update/add annotations and link controllers in Swagger config when needed.

## Useful links
- Project instructions: `/copilot-instructions.md`
- Global agent standards: `/AGENTS.md`
- Scope/roadmap: `/PROJECT_ANALYSIS_AND_ROADMAP_2025-11-15.md`
- Workflow guide: `/docs/guides/WORKFLOW_GUIDE.md`
- Feature flags: `/fwber-backend/config/features.php`
- Swagger scan paths: `/fwber-backend/config/l5-swagger.php`
