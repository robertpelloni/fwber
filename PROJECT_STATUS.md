# PROJECT_STATUS.md - fwber v1.8.4 (Hetzner Nginx Sync Helper Integration)

**Date:** 2026-04-05
**Version:** 1.8.4 "Hetzner Nginx Sync Helper Integration"
**Status:** ✅ **HETZNER NOW HAS A ROOT-OWNED NGINX SYNC PATH FOR GITHUB DEPLOYS**

---

## 🎯 What This Release Delivered
This release extends the prior Hetzner deploy privilege repair into a reproducible ops path.

Delivered:
- deploy script now prefers a dedicated root-owned nginx sync helper
- live Hetzner host provisioned with `/usr/local/bin/fwber-sync-nginx-sites`
- deploy user granted narrow passwordless sudo for that helper

## ✅ Why This Matters
The deploy workflow no longer has to rely on blanket sudo permissions for raw file-copy commands just to refresh nginx configs. This is a safer and more reproducible model for GitHub-triggered Hetzner deploys.
