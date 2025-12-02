# Feature Flags

This project uses feature flags to keep the MVP small and safe while allowing advanced systems to be enabled later. Flags live in `fwber-backend/config/features.php` and are enforced via the `feature` middleware alias registered in `fwber-backend/bootstrap/app.php`.

When a feature is disabled, its routes still register but requests return HTTP 404 to prevent discovery.

## Flags and route mapping

- FEATURE_GROUPS (default: true)
  - Scope: Core Groups system
  - Routes: `Api\GroupController`, `Api\GroupMessageController` (currently treated as core; not gated)
- FEATURE_PHOTOS (default: true)
  - Scope: Photo management
  - Routes: `PhotoController` (core; not gated)
- FEATURE_PROXIMITY_ARTIFACTS (default: true)
  - Scope: Proximity feed + artifacts
  - Routes (gated):
    - `GET /api/proximity/feed`
    - `GET /api/proximity/local-pulse`
    - `POST /api/proximity/artifacts`
    - `GET /api/proximity/artifacts/{id}`
    - `POST /api/proximity/artifacts/{id}/flag`
    - `DELETE /api/proximity/artifacts/{id}`
- FEATURE_CHATROOMS (default: false)
  - Scope: Real-time chatrooms
  - Routes (gated):
    - `/api/chatrooms/*`
    - `/api/chatrooms/{chatroomId}/messages/*`
- FEATURE_PROXIMITY_CHATROOMS (default: false)
  - Scope: Proximity networking chatrooms
  - Routes (gated):
    - `/api/proximity-chatrooms/*`
    - `/api/proximity-chatrooms/{chatroomId}/messages/*`
- FEATURE_RECOMMENDATIONS (default: false)
  - Scope: AI-powered recommendations
  - Routes (gated): `/api/recommendations/*`
- FEATURE_WEBSOCKET (default: false)
  - Scope: WebSocket/Mercure helpers for bi-directional updates
  - Routes (gated): `/api/websocket/*`
- FEATURE_CONTENT_GENERATION (default: false)
  - Scope: AI content generation & optimization
  - Routes (gated): `/api/content-generation/*`
- FEATURE_RATE_LIMITS (default: false)
  - Scope: Admin/diagnostic rate limit tools
  - Routes (gated): `/api/rate-limits/*`
- FEATURE_ANALYTICS (default: false)
  - Scope: Admin analytics
  - Routes (gated): `/api/analytics*`

## Enabling or disabling features

Prefer environment variables in `.env`:

```env
# Core (defaults are typically true)
FEATURE_GROUPS=true
FEATURE_PHOTOS=true
FEATURE_PROXIMITY_ARTIFACTS=true

# Advanced (defaults are typically false)
FEATURE_CHATROOMS=false
FEATURE_PROXIMITY_CHATROOMS=false
FEATURE_RECOMMENDATIONS=false
FEATURE_WEBSOCKET=false
FEATURE_CONTENT_GENERATION=false
FEATURE_RATE_LIMITS=false
FEATURE_ANALYTICS=false
```

After changing `.env`, clear Laravel config cache in production:

```bash
php artisan config:clear
php artisan config:cache
```

> Note: `php artisan route:list` will still display gated routes even when a flag is disabled. Actual requests will return 404 due to the middleware.

## Testing your flag changes

1. Toggle the desired flag in `.env` (e.g., `FEATURE_CHATROOMS=true`).
2. Clear and rebuild Laravel config cache:

   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

3. Make a request to a gated route (e.g., `GET /api/chatrooms`).
   - Enabled: expect normal 2xx/4xx from controller policies.
   - Disabled: expect `404 {"error":"Not Found"}` from the `feature` middleware.

## Troubleshooting

- I enabled a flag but still get 404
  - Ensure you ran `php artisan config:clear` followed by `php artisan config:cache`.
  - Verify the flag name matches `config/features.php` (e.g., `FEATURE_PROXIMITY_CHATROOMS`).
  - Check for typos or whitespace in `.env`.

- I don’t see my new flag taking effect
  - Confirm you added the key in `config/features.php` and used the same key in the route middleware: `->middleware('feature:<key>')`.
  - If using a deployment cache, make sure application and opcache are refreshed.

## Adding a new feature flag

1. Add a key in `fwber-backend/config/features.php`:
   ```php
   'new_feature' => env('FEATURE_NEW_FEATURE', false),
   ```
2. Gate related routes with middleware:
   ```php
   Route::prefix('new-feature')->middleware('feature:new_feature')->group(fn () => { /* ... */ });
   ```
3. Document the flag here and update `.env.example` if an env var is needed.
