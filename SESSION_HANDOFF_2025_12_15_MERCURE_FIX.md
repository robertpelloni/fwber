# Session Handoff: Mercure CORS Fix

## Issue
The user reported a CORS error when connecting to the Mercure hub:
`Cross-Origin Request Blocked: The Same Origin Policy disallows reading the remote resource at https://mercure.fwber.me/.well-known/mercure?topic=%2Fusers%2F1.`

## Root Cause
The Mercure service (running in the `mercure` container) was not configured to allow Cross-Origin requests from `https://fwber.me`. The `MERCURE_CORS_ALLOWED_ORIGINS` environment variable was likely missing or incorrect in the production environment.

## Fixes Applied
1.  **Updated `docker-compose.prod.yml`**:
    -   Added a default value for `MERCURE_CORS_ALLOWED_ORIGINS` if the environment variable is not set.
    -   Default: `https://fwber.me https://www.fwber.me` (Space-separated list).
    -   This ensures that even without an explicit `.env` entry, the production domains are allowed.

2.  **Updated `fwber-backend/.env.example`**:
    -   Added `MERCURE_CORS_ALLOWED_ORIGINS` with a space-separated list of example origins (localhost and production).
    -   This serves as a reference for future deployments.

## Required Actions
To apply this fix in production, the user needs to:

1.  **Pull the latest code**:
    ```bash
    git pull origin main
    ```

2.  **Recreate the Mercure container**:
    ```bash
    docker-compose -f docker-compose.prod.yml up -d --force-recreate mercure
    ```
    *Note: If using a different deployment method (e.g., `deploy.sh`), ensure the `mercure` service is restarted.*

3.  **(Optional) Update `.env`**:
    -   Add `MERCURE_CORS_ALLOWED_ORIGINS="https://fwber.me https://www.fwber.me"` to the `.env` file on the server if specific customization is needed.

## Verification
After redeploying, the frontend at `https://fwber.me` should be able to connect to `https://mercure.fwber.me` without CORS errors.
