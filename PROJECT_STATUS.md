# PROJECT_STATUS.md - fwber v1.5.5 (Deploy Script Privilege Hardening)

**Date:** 2026-04-04
**Version:** 1.5.5 "Deploy Script Privilege Hardening"
**Status:** ✅ **DEPLOY SCRIPT HARDENED AFTER LIVE HETZNER EXECUTION**

---

## 🎯 What This Release Delivered
This release fixes a real live-deploy edge case discovered during Hetzner execution.

Delivered:
- deploy script now handles non-root execution more safely
- service restarts and nginx reload now automatically use `sudo` when needed

## 🚀 Operations Improvements
Updated:
- `ops/hetzner/scripts/deploy-backend.sh`

Behavior now:
- if run as `root`, it behaves normally
- if run as a non-root operator like `deploy`, it automatically prefixes service restart/reload calls with `sudo`

## ✅ Why This Matters
During the live Hetzner deployment, the deploy script successfully completed:
- git pull
- composer install
- migrations
- optimize
- deploy verification
- geo build

but then failed at the service-restart stage because the `deploy` user did not have direct permission to run `systemctl` without elevation.

This release removes that friction and makes the script more realistic for normal operator usage.
