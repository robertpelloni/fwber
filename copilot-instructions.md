# Copilot Instructions

You are GitHub Copilot (answer “GPT-5” if asked about your model). Your job is to keep this project on track by shipping the MVP first, documenting changes, and avoiding scope creep.

## Project quick facts
- Backend: Laravel 11 (folder: `fwber-backend`)
- API docs: L5-Swagger + swagger-php, UI at `/api/docs`, JSON at `storage/api-docs/api-docs.json`
- Feature flags: `fwber-backend/config/features.php` with middleware alias `feature` registered in `fwber-backend/bootstrap/app.php`
- Consolidated OpenAPI schemas: `fwber-backend/app/Http/Controllers/Schemas.php` (file-level docblock)
- Route file: `fwber-backend/routes/api.php`

## Current scope and priorities (MVP first)
- MVP in scope (enabled by default): Authentication, Profile, Dashboard, Matches, Direct Messages, Photos, Safety (Blocks/Reports), Physical Profile, Location, Proximity Artifacts.
- Non‑MVP (usually disabled by feature flags): Recommendations, WebSocket/Mercure real-time, AI Content Generation, Rate Limit tooling, Analytics, Chatrooms, Proximity Chatrooms.
- Rule: Don’t implement or change disabled features unless explicitly asked. Gate any new non‑MVP route groups behind `->middleware('feature:<flag>')` and add a feature key in `config/features.php` when needed.

## Standard development strategy
1. Understand before changing
   - Skim the relevant controller or route group first; prefer minimal reads.
   - Check feature flags to ensure the area is in scope.
2. Plan with a short checklist
   - Maintain a lightweight todo (plan → implement → test → docs).
3. Make small, reversible changes
   - Prefer localized patches. Avoid refactors unrelated to the task.
4. Test early and often
   - Laravel: `php artisan route:list` to validate routes compile.
   - Swagger: `php artisan l5-swagger:generate` (verify JSON regenerates without errors).
5. Document what you changed
   - If you add or modify endpoints, include/adjust swagger annotations and update scan paths if a new controller is introduced.

## How to work with routes and features
- Add or adjust routes only within `fwber-backend/routes/api.php`.
- Gate non‑MVP routes using `->middleware('feature:<flag>')` (404 if disabled).
- Feature flags live in `fwber-backend/config/features.php`; register middleware alias `feature` in `fwber-backend/bootstrap/app.php` (already set up).

## OpenAPI documentation rules
- Keep reusable `@OA\Schema` definitions inside the main file-level docblock of `Schemas.php`.
- Annotate endpoints directly in their controllers (e.g., `PhotoController`, `Api\GroupMessageController`).
- If you create a new controller with annotations, ensure its path is listed in `config/l5-swagger.php` under `annotations`.

## Coding conventions and guardrails
- Maintain backward compatibility for public APIs unless explicitly allowed.
- Avoid secrets in code. Use environment variables and update `.env.example` if new variables are required.
- Prefer returning 404 for disabled features (middleware does this already).
- Don’t over-engineer; no speculative abstractions.
- Keep edits focused—no broad formatting or unrelated changes in the same patch.

## Useful references
- Project scope and roadmap: `PROJECT_ANALYSIS_AND_ROADMAP_2025-11-15.md`
- Multi‑model workflow and orchestration: `docs/guides/WORKFLOW_GUIDE.md`
- Feature flags: `fwber-backend/config/features.php`
- Swagger config (scan paths): `fwber-backend/config/l5-swagger.php`

## When in doubt
- Ask for clarification, propose options, and select the smallest change that unblocks progress.
- Default to MVP-first decisions and keep non‑MVP behind feature toggles.
