# PROJECT_STATUS.md - fwber v1.5.6 (WebSocket Smoke Handshake Fix)

**Date:** 2026-04-04
**Version:** 1.5.6 "WebSocket Smoke Handshake Fix"
**Status:** ✅ **PUBLIC HETZNER CUTOVER VALIDATION TUNED AFTER LIVE SMOKE TESTING**

---

## 🎯 What This Release Delivered
This release fixes a false-negative discovered during the first full public Hetzner smoke run after cutover.

Delivered:
- corrected websocket smoke handshake key in the smoke-check script
- removed a probe-side failure that was masking an otherwise healthy `ws.fwber.me` endpoint

## 🚀 Operations Improvements
Updated:
- `ops/hetzner/scripts/smoke-check.sh`

The websocket probe now uses a valid RFC-compliant `Sec-WebSocket-Key`, matching the successful manual handshake that already proved Reverb/nginx were functioning correctly.

## ✅ Why This Matters
The previous live smoke run showed:
- API healthy
- geo healthy
- websocket endpoint healthy when tested manually
- but smoke script websocket probe failed with `400 Invalid Sec-WebSocket-Key`

That meant the failure was in the probe, not the service. This release fixes that mismatch so the smoke summary can reflect the real runtime state.
