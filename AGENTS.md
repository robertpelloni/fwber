# AGENTS

This document establishes shared standards for all AI assistants and tools working on this repository.

## Mission and scope
- **Current Phase**: Post-MVP / Secondary Systems.
- **Completed MVP**: Auth, Profile, Dashboard, Matches, Direct Messages, Photos, Safety, Physical Profile, Location, Proximity Artifacts.
- **Active Focus**: Refinement of Secondary Systems (Recommendations, AI Content, Chatrooms) and Real-time features (WebSocket/Mercure).

## Standard workflow
1. **Check Status**: Read `PROJECT_STATUS_2025-11-24.md` for the latest state.
2. **Understand**: Read only what you need.
3. **Plan**: Maintain a concise checklist.
4. **Implement**: Small, targeted patches.
5. **Test**: Verify routes and run Cypress tests.
6. **Document**: Update `PROJECT_STATUS_2025-11-24.md` if significant milestones are reached.

## Guardrails (Do/Don’t)
- Do gate non‑MVP routes with `->middleware('feature:<flag>')`.
- Do keep `@OA\Schema` definitions centralized in `fwber-backend/app/Http/Controllers/Schemas.php`.
- Do prefer incremental, localized changes.
- Don’t implement disabled features unless explicitly requested.
- Don’t refactor broadly or change formatting unrelated to the task.

## Handover notes
- **Primary Status Doc**: `PROJECT_STATUS_2025-11-24.md`.
- **Feature Flags**: See `docs/FEATURE_FLAGS.md`.
- **Workflow**: See `docs/guides/WORKFLOW_GUIDE.md`.

## Adding a new feature flag
See `docs/FEATURE_FLAGS.md` for the authoritative guide. In short:
- Add a key in `fwber-backend/config/features.php`, e.g. `'new_feature' => env('FEATURE_NEW_FEATURE', false)`
- Gate routes with `->middleware('feature:new_feature')` in `fwber-backend/routes/api.php`
- Extend `fwber-backend/.env.example` with `FEATURE_NEW_FEATURE=false` and document it in `docs/FEATURE_FLAGS.md`

## Model-specific guidance
- Copilot: see `copilot-instructions.md`
- Claude: see `docs/ai-models/CLAUDE.md`
- Others: follow this AGENTS standard and the docs referenced above.
