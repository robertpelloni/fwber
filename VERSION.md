# Version Reference

- **Current version:** `1.0.6`
- **Release date:** `2026-04-01`
- **Release name:** `Payments, Notifications & Realtime Hardening`

## Summary

This release hardens the remaining live production paths by guarding Stripe initialization when the publishable key is missing, routing notification fetches through the shared API client, adding a notifications count endpoint, and preventing realtime from guessing broken production websocket hosts.

## Version Sources of Truth

- Root version file: `VERSION`
- Root package metadata: `package.json`
- Frontend package metadata: `fwber-frontend/package.json`
- Historical release notes: `CHANGELOG.md`
