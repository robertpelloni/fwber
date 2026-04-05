# PROJECT_STATUS.md - fwber v1.8.5 (Smoke Check Timeout + Roast Fallback Hardening)

**Date:** 2026-04-05
**Version:** 1.8.5 "Smoke Check Timeout + Roast Fallback Hardening"
**Status:** ✅ **DEPLOY VALIDATION PATH IS HARDER TO STALL, AND PUBLIC ROAST PREVIEW IS SAFER IN PRODUCTION**

---

## 🎯 What This Release Delivered
This release hardens two live deploy blockers discovered during Hetzner workflow verification:
- bounded timeout on the websocket smoke probe
- broader fallback handling for public roast preview generation

## ✅ Why This Matters
The backend deploy should not hang for 10 minutes because a websocket handshake never closes, and a public preview endpoint used by smoke checks should not return a 500 just because an AI driver throws a non-standard throwable.
