# PROJECT_STATUS.md - fwber v1.4.2 (Hetzner Ops Templates & CI Env Alignment)

**Date:** 2026-04-04
**Version:** 1.4.2 "Hetzner Ops Templates & CI Env Alignment"
**Status:** ✅ **VERIFIED, COMMITTED, AND READY FOR VPS EXECUTION**

---

## 🎯 What This Release Delivered
This release turns the new Hetzner/Vercel deployment recommendation into concrete operational assets.

Delivered:
- copy-ready Hetzner **Nginx vhost templates**
- copy-ready Hetzner **systemd service units** for queue, Reverb, and geo
- copy-ready Hetzner **bootstrap** and **deploy** shell scripts
- corrected frontend CI env values so the GitHub build workflow reflects the actual frontend runtime contract
- corrected frontend production env example to use `NEXT_PUBLIC_API_URL` without `/api` and Reverb env vars instead of legacy realtime config

## 🧰 New Operational Assets
Added under `ops/hetzner/`:
- `nginx/api.fwber.me.conf`
- `nginx/ws.fwber.me.conf`
- `nginx/geo.fwber.me.conf`
- `systemd/fwber-queue.service`
- `systemd/fwber-reverb.service`
- `systemd/fwber-geo.service`
- `scripts/bootstrap-ubuntu.sh`
- `scripts/deploy-backend.sh`

## 🔧 Alignment Fixes
- `.github/workflows/frontend-build.yml` now uses:
  - `NEXT_PUBLIC_API_URL=https://api.fwber.me`
  - `NEXT_PUBLIC_REVERB_HOST=ws.fwber.me`
  - `NEXT_PUBLIC_REVERB_PORT=443`
  - `NEXT_PUBLIC_REVERB_SCHEME=https`
- `fwber-frontend/.env.production.example` now matches the active frontend contract instead of the old `/api`-suffixed and Mercure-oriented example.
- Hetzner deployment docs now point directly to the new `ops/hetzner/` templates.

## ✅ Validation
- `bash -n ops/hetzner/scripts/bootstrap-ubuntu.sh`
- `bash -n ops/hetzner/scripts/deploy-backend.sh`
- `npm run build --prefix fwber-frontend`
- Result: scripts validated and frontend production build passed.

## ✅ Why This Matters
The docs refresh in v1.4.1 established the right architecture. This release adds the actual reusable artifacts needed to execute that architecture quickly once the Hetzner VPS is provisioned.
