# PROJECT_STATUS.md - fwber v1.6.1 (GitHub Hetzner Deploy Rust Path Fix)

**Date:** 2026-04-04
**Version:** 1.6.1 "GitHub Hetzner Deploy Rust Path Fix"
**Status:** ✅ **GITHUB HETZNER DEPLOY FAILURE ROOT-CAUSED AND PATCHED**

---

## 🎯 What This Release Delivered
This release fixes the first real GitHub Hetzner deployment failure after switching CI away from DreamHost.

Delivered:
- root-caused the GitHub deploy failure to non-login SSH sessions using the old system Cargo instead of rustup
- patched the Hetzner deploy script to explicitly source rustup's cargo environment before geo builds

## ✅ Why It Failed
The server was correctly provisioned with a modern rustup toolchain, but GitHub Actions invoked the deploy script through a non-login SSH session, which did not load the deploy user's rustup PATH.

That made `fwber-geo` build with the old system Cargo and fail on `edition2024`.
