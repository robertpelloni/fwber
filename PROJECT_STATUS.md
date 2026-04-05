# PROJECT_STATUS.md - fwber v1.8.1 (Wallet Surface Restoration)

**Date:** 2026-04-05
**Version:** 1.8.1 "Wallet Surface Restoration"
**Status:** ✅ **WALLET DEAD LINKS NOW RESOLVE TO A REAL SURFACE AGAIN**

---

## 🎯 What This Release Delivered
This release restores the compact wallet surface required by several still-active routes and upsell paths.

Delivered:
- wallet backend payload + address update endpoints
- wallet schema support for referral code, wallet address, token balance, and transactions
- frontend `/wallet` page

## ✅ Why This Matters
`/wallet` was still referenced by event payment, gift, filter, and notification flows. Restoring it removes another major dead-route cluster from the live signed-in experience.
