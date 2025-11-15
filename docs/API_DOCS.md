# API Docs Quickstart

The API is documented using L5-Swagger + swagger-php (OpenAPI 3). This guide explains how to generate the docs, where to find them, and the project-specific conventions.

## View the docs
- UI: `http://localhost:8000/api/docs` (or `/docs` depending on your config)
- JSON: `fwber-backend/storage/api-docs/api-docs.json`

### Tag overview
- Authentication
- Profile
- Dashboard
- Matches
- Messages
- Groups
- Photos
- Physical Profile
- Profile Views
- Bulletin Boards
- Chatrooms
- Proximity Chatrooms
- Proximity Artifacts
- Recommendations
- WebSocket
- Mercure
- Content Generation
- Rate Limiting
- Health
- Analytics
- Moderation

## Generate the docs
Run from `fwber-backend/`:

```bash
php artisan l5-swagger:generate
```

If the command fails, check the Troubleshooting section below.

Optional helper (Windows/PowerShell):

```powershell
# From repo root
powershell -ExecutionPolicy Bypass -File .\tools\update-openapi.ps1
# Or skip copying to docs/postman
powershell -ExecutionPolicy Bypass -File .\tools\update-openapi.ps1 -SkipCopy
```

## Import into Postman
Postman can import OpenAPI 3.0 directly:

1. Open Postman → Import
2. Select the file `fwber-backend/storage/api-docs/api-docs.json` (or the copy in `docs/postman/fwber-openapi.json`)
3. Choose "Generate collection"
4. Save. You can now send requests from the generated collection.

Tip: If feature flags are disabled, some routes will return 404. See the next section to enable them for testing.

See also:
- `docs/postman/README.md` for a ready-to-import OpenAPI JSON and quick copy command
- `docs/testing/SMOKE_TESTS.md` for fast manual smoke checks

## Annotation conventions
- Reusable schemas go in a single file-level docblock in:
  - `fwber-backend/app/Http/Controllers/Schemas.php`
- Endpoint annotations live in controllers (e.g., `PhotoController`, `Api/GroupMessageController`).
- If you add a new annotated controller, include its path in:
  - `fwber-backend/config/l5-swagger.php` under the `annotations` array.

## Feature flags and endpoints
Advanced/non-MVP routes are gated by feature flags via middleware and may return 404 if disabled. See:
- `docs/FEATURE_FLAGS.md`
- `fwber-backend/config/features.php`

Common flags to enable while testing specific tags:
- Recommendations → `FEATURE_RECOMMENDATIONS=true`
- WebSocket → `FEATURE_WEBSOCKET=true`
- Content Generation → `FEATURE_CONTENT_GENERATION=true`
- Rate Limiting → `FEATURE_RATE_LIMITS=true`

## Troubleshooting
- I see a `$ref` or unresolved schema error:
  - Ensure the referenced `@OA\Schema` is defined in `Schemas.php` (the central docblock). Order matters for swagger-php; centralizing avoids resolution issues.
- My new controller’s annotations don’t show up:
  - Add the controller path to `config/l5-swagger.php` `annotations`.
  - Re-run `php artisan l5-swagger:generate`.
- The UI 404s:
  - Confirm the l5-swagger routes are enabled and app is running (`php artisan serve`).
  - Check the docs route (common paths: `/api/docs`, `/docs`, `/documentation`).
