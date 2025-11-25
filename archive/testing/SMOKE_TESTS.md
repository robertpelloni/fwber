# API Smoke Tests

This guide lists minimal smoke tests to validate the most important endpoints and feature-flagged routes in Swagger UI or Postman.

Prerequisites:
- Backend running locally (e.g., `php artisan serve` with `APP_URL=http://localhost:8000`)
- A valid Bearer token for authenticated endpoints
- Feature flags set in `fwber-backend/.env` (see `docs/FEATURE_FLAGS.md`)

## Enable feature flags (optional)
Set these to `true` to test non-MVP routes:

```
FEATURE_RECOMMENDATIONS=true
FEATURE_WEBSOCKET=true
FEATURE_CONTENT_GENERATION=true
FEATURE_RATE_LIMITS=true
```

Restart the app after changing flags.

## Quick checks in Swagger UI
Open: http://localhost:8000/api/documentation

- Health → GET /health: expect 200
- Authentication → POST /auth/login: expect 200 and token

If authenticated:
- Profile → GET /profile: expect 200 (user payload)

## Feature-flagged routes
When feature flags are enabled and using a valid token:

- Recommendations → GET /recommendations
  - Expect 200 with `RecommendationList` schema
  - Try query params: `limit=5&types[]=ai&types[]=location`

- Content Generation → POST /content-generation/optimize
  - Expect 200 or 400 (BadRequest) depending on input

- Rate Limiting → GET /rate-limits/status/{action}
  - Expect 200 with `RateLimitStatus` schema

- WebSocket → POST /websocket/connect
  - Expect 200 with connection status payload

## Optional PowerShell examples

```powershell
# Replace with a valid JWT
$token = "REPLACE_ME_JWT"
$base = "http://localhost:8000/api"

# Recommendations
Invoke-RestMethod -Method Get -Uri "$base/recommendations?limit=3" -Headers @{ Authorization = "Bearer $token" } | ConvertTo-Json -Depth 6

# Content Generation (example payload)
$body = @{ text = "Make this shorter" } | ConvertTo-Json
Invoke-RestMethod -Method Post -Uri "$base/content-generation/optimize" -Headers @{ Authorization = "Bearer $token"; "Content-Type" = "application/json" } -Body $body | ConvertTo-Json -Depth 6
```

Troubleshooting:
- 401 Unauthorized → confirm Bearer token and `Authorization` header
- 404 Not Found → confirm feature flag enabled and route exists (`php artisan route:list`)
- 500 Error → check application logs
