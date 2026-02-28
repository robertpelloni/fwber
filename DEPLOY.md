# DEPLOY.md — The fwber Operations Guide

> **Last Updated:** 2026-02-27 by Claude (Antigravity)
> **Version:** 0.3.49

This document serves as the single source of truth for deploying the fwber monorepo across various environments.

---

## 🏗️ 1. Local Development (Docker Compose)

The fastest way to spin up the entire stack locally is via Docker Compose.

1. **Prerequisites**: Docker Desktop installed.
2. **Setup Env**:
   ```bash
   cp fwber-backend/.env.example fwber-backend/.env
   cp fwber-frontend/.env.example fwber-frontend/.env.local
   ```
3. **Boot the Cluster**:
   ```bash
   docker-compose -f docker-compose.dev.yml up -d
   ```
4. **Initialize DB**:
   ```bash
   docker-compose exec app php artisan migrate --seed
   ```
5. **Access**: Frontend at `http://localhost:3000`, Backend API at `http://localhost:8000`.

---

## 🚀 2. Production: Vercel (Frontend)

The Next.js 16 frontend is optimized for deployment on Vercel.

1. **Dashboard Setup**: Import the GitHub repository into Vercel.
2. **Root Directory**: In Vercel Project Settings > General, set the Root Directory to `fwber-frontend`.
3. **Environment Variables**: Add the following to Vercel:
   * `NEXT_PUBLIC_API_URL=https://api.fwber.com/api`
   * `NEXT_PUBLIC_WEBSOCKET_HOST=api.fwber.com`
   * `NEXT_PUBLIC_WEBSOCKET_PORT=443`
4. **Build Optimization**: Set the Ignored Build Step to prevent backend commits from triggering frontend builds:
   `git diff --quiet HEAD^ HEAD ./`

---

## 🐘 3. Production: DreamHost (Backend API)

DreamHost provides sturdy shared/VPS hosting, but requires specific configuration to handle Laravel 12 and the queue workers.

1. **PHP Version**: Ensure the domain is set to use PHP 8.3+.
2. **Document Root**: Point the domain's web directory to `/path/to/fwber/fwber-backend/public`.
3. **Queue Worker**: On shared hosting, you must use a CRON job to process the queue if `supervisor` is unavailable:
   `* * * * * cd /path/to/fwber/fwber-backend && php artisan schedule:run >> /dev/null 2>&1`
4. **Environment Tweaks (`fwber-backend/.env`)**:
   ```ini
   APP_ENV=production
   APP_DEBUG=false
   
   # Shared Hosting Optimization - Avoids file locking issues
   CACHE_STORE=database
   QUEUE_CONNECTION=database
   SESSION_DRIVER=database
   
   # Security
   CORS_ALLOWED_ORIGINS="https://fwber.vercel.app,https://fwber.com"
   ```

---

## ⚓ 4. Production: Kubernetes (Enterprise Scale)

For high-density scaling, use the provided Helm charts or K8s manifests in the `/kubernetes` directory.

1. **Cluster Requirements**: NGINX Ingress Controller, Cert-Manager, and an external managed database (e.g., AWS RDS PostgreSQL with PostGIS enabled).
2. **Secrets**:
   ```bash
   kubectl create secret generic fwber-secrets --from-env-file=k8s.env
   ```
3. **Deploy**:
   ```bash
   kubectl apply -f kubernetes/
   ```
4. **Real-time Scaling**: The `laravel-echo-server` or `reverb` pods require sticky sessions in the Ingress configuration to maintain WebSocket connections reliably.

---

## 🩺 5. Post-Deployment Verification

Always run this checklist after a major version bump:
1. [ ] Check the `VERSION` file in production matches `CHANGELOG.md`.
2. [ ] Ping `/api/system` (or `/admin/system` via browser) to ensure DB and Cache paths are green.
3. [ ] Perform a test login.
4. [ ] Upload a test photo (verifies S3/Disk permissions).
5. [ ] Establish a test chat (verifies WebSocket persistence).

---

## 🌍 6. Multi-Region & CDN Edge Caching

To scale globally, the fwber architecture relies on **Edge Caching** (via Vercel for the frontend, and Cloudflare for the backend API). 

### Backend (Cloudflare)
1. Ensure the domain is proxied (Orange Cloud) through Cloudflare.
2. In Cloudflare **Caching rules**, create a rule bypassing cache entirely for any request containing the `Authorization` header, or routes matching `*/api/*` (excluding Health routes). 
3. The Laravel app internally applies `EdgeCacheResponse` (alias: `edge.cache`) to globally safe endpoints like `/.well-known/webfinger` and `/api/health`. Cloudflare will automatically respect these `s-maxage` parameters and serve responses directly from the Edge.
4. **Data Spillage Warning**: Never attach `edge.cache` to endpoints dealing with `auth:sanctum`.

### Frontend (Vercel Edge)
Vercel automatically distributes static assets globally. For dynamic routes, rely on `SWR` (Stale-While-Revalidate) caching provided by Next.js's native `fetch` extended cache parameters, keeping TTFB as low as possible across regions.
