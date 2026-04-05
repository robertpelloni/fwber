# PROJECT_STATUS.md - fwber v1.7.2 (Hetzner Repo Ownership Repair)

**Date:** 2026-04-05
**Version:** 1.7.2 "Hetzner Repo Ownership Repair"
**Status:** ✅ **LIVE SERVER CHECKOUT PERMISSION DRIFT REPAIRED**

---

## 🎯 What This Release Delivered
This release records an infrastructure-state repair discovered during the latest automated deploy cycle.

Delivered:
- repaired ownership drift inside `/var/www/fwber/repo/.git`
- restored deploy-user control of the live checkout
- re-applied shared ACLs for backend logs so deploy-user and PHP-FPM can keep coexisting cleanly

## ✅ Why This Matters
The latest push-triggered Hetzner backend deploy failed not because of application code, but because the server checkout itself had drifted to mixed ownership. That is now repaired.
