# FWBer Deployment Guide

This guide describes how to deploy the FWBer application (Backend and Frontend) to production.

## Prerequisites

- **Backend:**
  - PHP 8.2+
  - Composer
  - MySQL 8.0+ or PostgreSQL 13+
  - Nginx/Apache
  - Supervisor (for queue workers)

- **Frontend:**
  - Node.js 18+
  - NPM
  - PM2 (Process Manager)

## Backend Deployment

The backend is a Laravel application. A deployment script `deploy.sh` is provided in the `fwber-backend` directory.

### Configuration

1.  Ensure `.env` is configured for production:
    ```ini
    APP_ENV=production
    APP_DEBUG=false
    ```
2.  Configure database connection.
3.  Configure mail and other services.

### Deployment Steps

1.  Navigate to the backend directory:
    ```bash
    cd fwber-backend
    ```
2.  Run the deployment script:
    ```bash
    ./deploy.sh --env=production --branch=main
    ```

    **Options:**
    - `--env=production`: Set the environment (default: production).
    - `--branch=main`: Set the branch to deploy (default: main).
    - `--skip-migrations`: Skip running database migrations.
    - `--skip-backup`: Skip database backup before migrations.
    - `--dry-run`: Simulate the deployment without making changes.

### Manual Steps (if script fails)

1.  Pull latest changes: `git pull origin main`
2.  Install dependencies: `composer install --no-dev --optimize-autoloader`
3.  Run migrations: `php artisan migrate --force`
4.  Cache config/routes: `php artisan config:cache && php artisan route:cache`
5.  Restart queues: `php artisan queue:restart`

## Frontend Deployment

The frontend is a Next.js application. A deployment script `deploy.sh` is provided in the `fwber-frontend` directory.

### Configuration

1.  Ensure `.env.local` (or `.env.production`) is configured:
    ```ini
    NEXT_PUBLIC_API_URL=https://api.yourdomain.com
    ```

### Deployment Steps

1.  Navigate to the frontend directory:
    ```bash
    cd fwber-frontend
    ```
2.  Run the deployment script:
    ```bash
    ./deploy.sh --env=production --branch=main
    ```

    **Options:**
    - `--env=production`: Set the environment (default: production).
    - `--branch=main`: Set the branch to deploy (default: main).
    - `--dry-run`: Simulate the deployment without making changes.

### Manual Steps (if script fails)

1.  Pull latest changes: `git pull origin main`
2.  Install dependencies: `npm ci`
3.  Build application: `npm run build`
4.  Restart application (using PM2): `pm2 reload fwber-frontend`

## Post-Deployment Verification

1.  **Backend Health Check:**
    - Visit `https://api.yourdomain.com/up` (or configured health endpoint).
    - Check logs: `storage/logs/laravel.log`.

2.  **Frontend Verification:**
    - Visit the application URL.
    - Log in and verify core functionality (Matching, Chat, Profile).

## Rollback

If a deployment fails, you can rollback to the previous commit.

### Backend Rollback

A `rollback.sh` script is available in `fwber-backend`.

```bash
cd fwber-backend
./rollback.sh
```

### Frontend Rollback

1.  Checkout previous commit: `git checkout HEAD^`
2.  Re-run deployment script: `./deploy.sh`
