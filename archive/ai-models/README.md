# AI Model Guides

This folder contains model-specific guidance for working in this repository. Each guide adapts the shared standards in `AGENTS.md` to the model’s strengths and interaction style.

## Available guides
- GitHub Copilot: [`COPILOT.md`](COPILOT.md)
- Claude (Anthropic): [`CLAUDE.md`](CLAUDE.md)

## Adding a new model guide
1. Create a new file in this folder, e.g. `YOURMODEL.md`.
2. Cover:
   - MVP-first scope and feature flag usage
   - Minimal-read, plan-first, small-change workflow
   - Laravel 11 + Swagger specifics (routes in `fwber-backend/routes/api.php`, flags in `fwber-backend/config/features.php`, schemas in `app/Http/Controllers/Schemas.php`, scan paths in `config/l5-swagger.php`)
   - Test steps (`php artisan route:list`, `php artisan l5-swagger:generate`)
3. Link your new file from `AGENTS.md` under “Model-specific guidance.”
