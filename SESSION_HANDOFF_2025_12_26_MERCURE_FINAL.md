# Session Handoff: Mercure Configuration Fix (Final)

## 🚨 Critical Action Required

The Mercure configuration has been updated to use the **Mercure Demo Hub** (`demo.mercure.rocks`) because self-hosting is not possible on the current shared hosting environment.

### 1. Pull Latest Changes
```bash
git pull origin main
```

### 2. Update Backend Environment (`fwber-backend/.env`)
You MUST update your production `.env` file on the server (VPS/DreamHost) to match the new configuration.

**Find these lines:**
```dotenv
MERCURE_PUBLIC_URL=https://api.fwber.me/.well-known/mercure
MERCURE_INTERNAL_URL=http://mercure/.well-known/mercure
```

**Replace with:**
```dotenv
MERCURE_PUBLIC_URL=https://demo.mercure.rocks/.well-known/mercure
MERCURE_INTERNAL_URL=https://demo.mercure.rocks/.well-known/mercure
```

**CRITICAL: Update JWT Secret**
The Demo Hub requires a specific secret key. You MUST set this exact value:
```dotenv
MERCURE_JWT_SECRET="!ChangeThisMercureHubJWTSecretKey!"
MERCURE_PUBLISHER_JWT_KEY="!ChangeThisMercureHubJWTSecretKey!"
MERCURE_SUBSCRIBER_JWT_KEY="!ChangeThisMercureHubJWTSecretKey!"
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
The frontend configuration (`NEXT_PUBLIC_MERCURE_URL`) must be updated.

**If using Vercel:**
1.  Go to Vercel Dashboard -> Settings -> Environment Variables.
2.  Update `NEXT_PUBLIC_MERCURE_URL` to `https://demo.mercure.rocks/.well-known/mercure`.
3.  Redeploy the project.

**If using Docker/VPS:**
1.  Update `fwber-frontend/.env.production` on the server.
2.  Rebuild and redeploy:
    ```bash
    ./deploy.sh --env=production
    ```

## Verification
1.  Open the app at `https://fwber.me`.
2.  Open Developer Tools -> Network Tab.
3.  Filter for `mercure`.
4.  Verify the request goes to `https://demo.mercure.rocks/.well-known/mercure...`.
5.  Verify the status is **200 OK** (EventStream).

## Why This Was Changed
-   **Constraint**: Shared hosting prevents running a custom Mercure instance.
-   **Solution**: Use the public Demo Hub (`demo.mercure.rocks`).
-   **Fix**: The backend was previously signing tokens with a custom key, which the Demo Hub rejected (401). We have now updated the backend to use the Demo Hub's required key (`!ChangeThisMercureHubJWTSecretKey!`).
