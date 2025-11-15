# AGENTS

This document establishes shared standards for all AI assistants and tools working on this repository. The goal is to ship the MVP first, protect scope with feature flags, and keep changes safe, small, and well-documented.

## Mission and scope
- Primary objective: Deliver a stable MVP covering Auth, Profile, Dashboard, Matches, Direct Messages, Photos, Safety, Physical Profile, Location, and Proximity Artifacts.
- Secondary systems (behind feature flags unless explicitly enabled): Recommendations, WebSocket/Mercure, AI Content Generation, Rate Limits tooling, Analytics, Chatrooms, Proximity Chatrooms.

## Standard workflow
1. Understand → read only what you need
2. Plan → maintain a concise checklist and acceptance criteria
3. Implement → small, targeted patches that are easy to review and revert
4. Test → verify routes, run Swagger generation, check obvious regressions
5. Document → update relevant README/roadmap and Swagger annotations

## Guardrails (Do/Don’t)
- Do gate non‑MVP routes with `->middleware('feature:<flag>')`.
- Do keep `@OA\Schema` definitions centralized in `fwber-backend/app/Http/Controllers/Schemas.php` (file-level docblock).
- Do add controller paths to `fwber-backend/config/l5-swagger.php` if you add annotated controllers.
- Do prefer incremental, localized changes with minimal blast radius.
- Don’t implement disabled features unless explicitly requested.
- Don’t refactor broadly or change formatting unrelated to the task.
- Don’t commit secrets—extend `.env.example` when introducing new env vars.

## Project structure (selected)
- Laravel backend: `fwber-backend/`
  - Routes: `routes/api.php`
  - Middleware alias config: `bootstrap/app.php`
  - Feature flags: `config/features.php` (middleware key: `feature`)
  - Swagger config: `config/l5-swagger.php`
  - Central schemas: `app/Http/Controllers/Schemas.php`
- Docs & guides: `docs/`

## Testing and validation
- Routes compile: `php artisan route:list`
- OpenAPI generate: `php artisan l5-swagger:generate` → check for errors and that docs update
- Optional quick checks: targeted endpoint requests when feasible

## Handover notes
- Read: `PROJECT_ANALYSIS_AND_ROADMAP_2025-11-15.md` for high-level scope.
- Follow: `docs/guides/WORKFLOW_GUIDE.md` for multi-model processes.
- Be explicit: When you diverge from MVP scope, record justification and toggle via feature flags.

## Adding a new feature flag
See `docs/FEATURE_FLAGS.md` for the authoritative guide. In short:
- Add a key in `fwber-backend/config/features.php`, e.g. `'new_feature' => env('FEATURE_NEW_FEATURE', false)`
- Gate routes with `->middleware('feature:new_feature')` in `fwber-backend/routes/api.php`
- Extend `fwber-backend/.env.example` with `FEATURE_NEW_FEATURE=false` and document it in `docs/FEATURE_FLAGS.md`

## Model-specific guidance
- Copilot: see `copilot-instructions.md`
- Claude: see `docs/ai-models/CLAUDE.md`
- Others: follow this AGENTS standard and the docs referenced above.
