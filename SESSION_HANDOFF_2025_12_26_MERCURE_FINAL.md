# Session Handoff: Mercure Configuration Fix (Final)

## 🚨 Critical Action Required

The Mercure configuration has been updated to use the self-hosted instance (`api.fwber.me`) instead of the public demo hub (`demo.mercure.rocks`). This is required to fix the 401 Unauthorized and CORS errors.

### 1. Pull Latest Changes
```bash
git pull origin main
```

### 2. Update Backend Environment (`fwber-backend/.env`)
You MUST update your production `.env` file on the server (VPS/DreamHost) to match the new configuration.

**Find these lines:**
```dotenv
MERCURE_PUBLIC_URL=https://demo.mercure.rocks/.well-known/mercure
MERCURE_INTERNAL_URL=https://demo.mercure.rocks/.well-known/mercure
```

**Replace with:**
```dotenv
MERCURE_PUBLIC_URL=https://api.fwber.me/.well-known/mercure
MERCURE_INTERNAL_URL=http://mercure/.well-known/mercure
# OR if running on host without Docker networking:
# MERCURE_INTERNAL_URL=http://localhost:3001/.well-known/mercure
```

**Verify JWT Secret:**
Ensure `MERCURE_JWT_SECRET` matches the key in `fwber-backend/Caddyfile`:
```dotenv
MERCURE_JWT_SECRET=fwber_mercure_secret_key_2025_secure_and_long
```

### 3. Redeploy Backend
Restart the backend services to pick up the new environment variables.
```bash
# If using Docker Compose
docker-compose restart backend

# If using the deploy script
./deploy.sh --env=production
```

### 4. Redeploy Frontend
The frontend configuration (`NEXT_PUBLIC_MERCURE_URL`) has been updated in `.env.production`. You must rebuild and redeploy the frontend.

```bash
# If using Vercel, this happens automatically on push.
# If using Docker/VPS:
./deploy.sh --env=production
```

## Verification
1.  Open the app at `https://fwber.me`.
2.  Open Developer Tools -> Network Tab.
3.  Filter for `mercure`.
4.  Verify the request goes to `https://api.fwber.me/.well-known/mercure...`.
5.  Verify the status is **200 OK** (EventStream).

## Why This Was Changed
-   **Previous State**: Frontend was pointing to `demo.mercure.rocks`, but Backend was signing tokens with a custom secret. This caused 401 errors because the demo hub didn't know our secret.
-   **New State**: Frontend points to our self-hosted Mercure instance (`api.fwber.me`), which is configured with the correct secret and CORS headers.
