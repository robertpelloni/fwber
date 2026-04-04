# PROJECT_STATUS.md - fwber v1.5.4 (Hetzner Backend Execution & Database Migration)

**Date:** 2026-04-04
**Version:** 1.5.4 "Hetzner Backend Execution & Database Migration"
**Status:** ✅ **HETZNER BACKEND DEPLOYED; PUBLIC API/GEO DNS CUTOVER STILL PENDING**

---

## 🎯 What This Release Delivered
This release marks the first real live-server deployment execution wave for the Hetzner backend stack.

Delivered on `5.161.250.43`:
- cloned repo to `/var/www/fwber/repo`
- installed missing PHP/Rust deployment dependencies
- upgraded Rust toolchain so `fwber-geo` can build with `edition2024`
- built `fwber-geo` release successfully
- provisioned local Hetzner MySQL for fwber
- imported the DreamHost fwber production database into Hetzner MySQL
- switched Hetzner backend runtime to local MySQL + local Redis
- enabled and started `fwber-queue`, `fwber-reverb`, and `fwber-geo`
- verified local backend health, geo responses, Redis availability, and websocket handshake success

## 🚀 Verified Hetzner Runtime State
Confirmed on Hetzner:
- `php artisan deploy:verify --json` → **healthy**
- Redis → active
- `fwber-queue` → active
- `fwber-reverb` → active
- `fwber-geo` → active
- websocket upgrade through nginx → **`101 Switching Protocols`**
- geo nearby endpoint locally → valid JSON response

## ⚠️ Remaining External Cutover Work
Still pending outside the repo/runtime itself:
- repoint `api.fwber.me` to Hetzner
- repoint `geo.fwber.me` to Hetzner
- issue or confirm TLS certs for `api.fwber.me` and `geo.fwber.me` on Hetzner after DNS cutover
- update any remaining external panel/DNS records so the new topology is actually public

## ✅ Why This Matters
The fwber backend is no longer only “planned” for Hetzner — the runtime now actually exists there with:
- local DB
- local Redis
- local queueing
- local Reverb
- local geo service

That significantly reduces the remaining migration work to public DNS/TLS cutover rather than core application deployment.
