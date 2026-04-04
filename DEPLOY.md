# DEPLOY.md — The fwber Operations Guide

> **Last Updated:** 2026-04-04
> **Version:** 1.4.1

This document is the operational source of truth for deploying the active fwber stack after the restoration phases. The recommended topology is now:

- **Frontend:** Vercel
- **Backend / Realtime / Geo / Data:** Hetzner VPS

This replaces the older DreamHost-centered production recommendation.

---

## 1. Recommended Production Topology

### Frontend (`fwber.me`)
Deploy `fwber-frontend` to **Vercel**.

### Hetzner VPS
Host the following on a single production VPS initially:
- `api.fwber.me` → Laravel backend
- `ws.fwber.me` → Laravel Reverb websocket server
- `geo.fwber.me` → Rust `fwber-geo` microservice
- MySQL
- Redis
- queue workers
- scheduler / cron

### Suggested VPS Size
- **Preferred:** 4–8 vCPU, 8–16 GB RAM, 80–160+ GB NVMe
- **Best practical target:** 8 vCPU / 16 GB when sharing capacity with other projects like `bobsgame.com`
- **Minimum acceptable starter:** 2 vCPU / 8 GB, with the expectation that you may resize upward later

---

## 2. DNS Layout

Point domains as follows:

- `fwber.me` → Vercel
- `www.fwber.me` → Vercel
- `api.fwber.me` → Hetzner VPS public IP
- `ws.fwber.me` → Hetzner VPS public IP
- `geo.fwber.me` → Hetzner VPS public IP

---

## 3. Vercel Frontend Configuration

### Vercel Project
- **Root Directory:** `fwber-frontend`
- **Build Command:** `npm run build`
- **Output Directory:** `.next`

### Required Environment Variables
```env
NEXT_PUBLIC_APP_URL=https://fwber.me
NEXT_PUBLIC_API_URL=https://api.fwber.me
NEXT_PUBLIC_REVERB_HOST=ws.fwber.me
NEXT_PUBLIC_REVERB_SCHEME=https
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=pk_live_...
NEXT_PUBLIC_FRONTEND_VERSION=<current frontend version>
NEXT_PUBLIC_PROJECT_VERSION=<current root version>
```

### Notes
- Do **not** append `/api` to `NEXT_PUBLIC_API_URL`
- Let Vercel own the apex/frontend DNS records
- Keep backend-only secrets out of Vercel

---

## 4. Hetzner Backend Stack

Install and configure the following on Ubuntu 24.04 LTS:
- Nginx
- PHP 8.4 + PHP-FPM
- Composer
- Node.js LTS (for build tooling where needed)
- MySQL
- Redis
- Rust toolchain (or deploy prebuilt geo binary)
- systemd services for workers/reverb/geo
- Certbot TLS certificates

### Directory Convention
Suggested path:
```bash
/var/www/fwber/repo/
```

Repository layout beneath it:
- `/var/www/fwber/repo/fwber-backend`
- `/var/www/fwber/repo/fwber-frontend`
- `/var/www/fwber/repo/fwber-geo`

---

## 5. Backend Environment (`fwber-backend/.env`)

Use a production-oriented shape like:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.fwber.me
FRONTEND_URL=https://fwber.me
SESSION_DOMAIN=.fwber.me
SANCTUM_STATEFUL_DOMAINS=fwber.me,www.fwber.me

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fwber
DB_USERNAME=fwber
DB_PASSWORD=CHANGE_ME

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=fwber
REVERB_APP_KEY=CHANGE_ME
REVERB_APP_SECRET=CHANGE_ME
REVERB_HOST=ws.fwber.me
REVERB_PORT=443
REVERB_SCHEME=https

GEO_SCREENER_ENABLED=true
GEO_SCREENER_URL=https://geo.fwber.me

PAYMENT_DRIVER=stripe
STRIPE_KEY=sk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

### Important Notes
- `QUEUE_CONNECTION=redis` is now preferred over older database-queue guidance
- `CACHE_STORE=redis` and `SESSION_DRIVER=redis` are the production recommendation
- backend production should assume active restored systems exist: AI, premium billing, merchant marketplace, websockets, geo

---

## 6. Deployment Commands

### Initial Backend Bring-Up
```bash
cd /var/www/fwber/repo/fwber-backend
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan optimize
```

### Re-Deploy Sequence
```bash
cd /var/www/fwber/repo
git pull origin main

cd fwber-backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize

cd ../fwber-geo
cargo build --release

sudo systemctl restart fwber-queue
sudo systemctl restart fwber-reverb
sudo systemctl restart fwber-geo
sudo systemctl reload nginx
```

---

## 7. Long-Running Services

Run the following under `systemd`:

### Queue Worker
```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=120
```

### Reverb
```bash
php artisan reverb:start --host=127.0.0.1 --port=8080
```

### Geo Service
```bash
/var/www/fwber/repo/fwber-geo/target/release/fwber-geo
```

### Scheduler
Use cron or a persistent scheduler process:
```cron
* * * * * cd /var/www/fwber/repo/fwber-backend && /usr/bin/php artisan schedule:run >> /var/log/fwber-scheduler.log 2>&1
```

---

## 8. Nginx Proxy Layout

### `api.fwber.me`
- Serve Laravel `public/`
- Pass PHP to `php8.4-fpm`

### `ws.fwber.me`
- Reverse proxy to Reverb on `127.0.0.1:8080`
- Ensure websocket upgrade headers are passed through

### `geo.fwber.me`
- Reverse proxy to Rust geo service on `127.0.0.1:8081`

The detailed Hetzner/Vercel production blueprint lives in:
- `docs/ai/deployment/hetzner-vercel-production.md`

Ready-to-copy infrastructure templates also live in:
- `ops/hetzner/nginx/`
- `ops/hetzner/systemd/`
- `ops/hetzner/scripts/`

---

## 9. Post-Deployment Verification

Always verify the following after a major version bump or infrastructure move:

1. [ ] Frontend Vercel deploy is green
2. [ ] `https://api.fwber.me/api/auth/login` returns expected validation/auth behavior
3. [ ] `/roast` works against the live API
4. [ ] `/premium` upgrade initiation works
5. [ ] `/merchant/register` and `/merchant/dashboard` work
6. [ ] `wss://ws.fwber.me` accepts websocket traffic
7. [ ] `https://geo.fwber.me` responds successfully
8. [ ] queue workers are processing jobs
9. [ ] scheduler is firing recurring tasks
10. [ ] migrations complete without duplicate-index or missing-column drift failures

---

## 10. Stripe Billing Go-Live Checklist

Run this checklist before enabling live billing:

1. [ ] Confirm frontend Vercel env contains `NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY`
2. [ ] Confirm backend `.env` contains `PAYMENT_DRIVER=stripe`, `STRIPE_SECRET`, and `STRIPE_WEBHOOK_SECRET`
3. [ ] Register the production Stripe webhook endpoint: `https://api.fwber.me/api/stripe/webhook`
4. [ ] Subscribe to premium and commerce relevant events:
   - `payment_intent.succeeded`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
5. [ ] Verify `/premium` opens Stripe checkout when configured
6. [ ] Verify marketplace purchases stop using mock mode and require real payment confirmation in production
7. [ ] Complete one live/test premium purchase and one marketplace purchase
8. [ ] Verify webhook-driven state changes are reflected in premium status and merchant payment history

---

## 11. Legacy Note

Earlier DreamHost deployment guidance should now be treated as **legacy reference only**. fwber’s active production recommendation is **Hetzner VPS + Vercel frontend**.
