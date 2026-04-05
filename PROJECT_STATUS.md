# PROJECT_STATUS.md - fwber v1.8.3 (Hetzner Deploy Privilege Recovery)

**Date:** 2026-04-05
**Version:** 1.8.3 "Hetzner Deploy Privilege Recovery"
**Status:** ✅ **LATEST DEPLOY FAILURE ROOT-CAUSED TO NGINX CONFIG WRITE PRIVILEGES; SCRIPT NOW DEGRADES SAFELY**

---

## 🎯 What This Release Delivered
This release hardens the Hetzner deployment script after observing a real GitHub Actions deployment failure.

Delivered:
- optional nginx config sync when deploy lacks passwordless sudo for filesystem writes
- retained strict privileged execution for required `nginx` validation and `systemctl` restarts
- non-interactive privileged helper paths to avoid hanging on password prompts in CI

## ✅ Why This Matters
The previous deploy already completed `git pull`, `composer install`, migrations, optimize, and `php artisan deploy:verify`, then failed only because `sudo cp` / `sudo ln` required a password. This patch keeps deployment moving when live nginx configs are already present and only config refresh lacks privileges.
