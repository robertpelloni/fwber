# DEPLOY.md — The fwber Operations Guide

> **Last Updated:** 2026-03-25
> **Version:** 0.99.1

This document serves as the single source of truth for deploying the fwber distributed architecture.

---

## 🚀 1. Production: Vercel (Frontend)

The Next.js 16.1 frontend is optimized for deployment on Vercel Edge Network.

1. **Dashboard Setup**: Import the GitHub repository into Vercel.
2. **Root Directory**: Set the Root Directory to `fwber-frontend`.
3. **Environment Variables**: Add the following to Vercel:
   * `NEXT_PUBLIC_API_URL=https://api.fwber.me` (CRITICAL: Do not include `/api` at the end)
   * `NEXT_PUBLIC_WS_URL=wss://ws.fwber.me`
4. **Build Settings**: 
   * Build Command: `npm run build`
   * Output Directory: `.next`
5. **DNS**: Point your main domain (`www.fwber.me`) to Vercel via A Record (`76.76.21.21`) or CNAME.

---

## 🐘 2. Production: DreamHost (Backend API)

DreamHost hosts the Laravel 12 API, Database, and WebSockets.

1. **PHP Version**: Ensure the domain is set to use PHP 8.3+.
2. **Document Root**: Point the `api.fwber.me` subdomain's web directory to `/home/user/fwber/fwber-backend/public`.
3. **Database**: MySQL 8+ hosted internally on DreamHost (`mysql.fwber.me`).
4. **Environment (`.env`)**:
   ```ini
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://api.fwber.me
   
   CACHE_STORE=database
   QUEUE_CONNECTION=database
   SESSION_DRIVER=database
   BROADCAST_DRIVER=reverb
   
   CORS_ALLOWED_ORIGINS="https://www.fwber.me,https://fwber.me"
   ```
5. **Deployment Command**:
   ```bash
   cd ~/fwber/fwber-backend && git pull origin main && php artisan config:clear && php artisan config:cache && php artisan migrate --force
   ```

---

## 📡 3. Production: WebSockets (Laravel Reverb)

The "Pulse" real-time engine requires a dedicated proxy setup on DreamHost.

1. **Subdomain**: Create `ws.fwber.me` as a Fully Hosted domain in DreamHost.
2. **DNS**: Point `ws.fwber.me` to the DreamHost server IP (NOT Vercel).
3. **The Proxy Bridge**: Place this `.htaccess` in the `ws.fwber.me` root directory:
   ```apache
   RewriteEngine On
   RewriteCond %{HTTP:Upgrade} =websocket [NC]
   RewriteRule ^(.*) http://127.0.0.1:8080/$1 [P,L]
   RewriteCond %{HTTP:Upgrade} !=websocket [NC]
   RewriteRule ^(.*) http://127.0.0.1:8080/$1 [P,L]
   ```
4. **Start the Engine**: Run the daemon in the background:
   ```bash
   nohup php artisan reverb:start --host=0.0.0.0 --port=8080 > reverb.log 2>&1 &
   ```

---

## 🦀 4. Production: Rust Geo-Screener (Optional)

For high-density scaling, run the `fwber-geo` microservice.

1. Compile: `cd fwber-geo && cargo build --release`
2. Run: `nohup ./target/release/fwber-geo > geo.log 2>&1 &`
3. Update backend `.env` to point to the Rust service URL.

---

## 🩺 5. Post-Deployment Verification

Always run this checklist after a major version bump:
1. [ ] Check `https://api.fwber.me/api/health` reports the correct version.
2. [ ] Verify Vercel build is Green.
3. [ ] Perform a test ZK-Identity verification (checks cryptography and DB).
4. [ ] Upload a test photo (verifies AWS S3 credentials).
5. [ ] Establish a test chat (verifies Reverb WebSocket persistence).
