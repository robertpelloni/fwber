# Version Reference

- **Current version:** `1.0.23`
- **Release date:** `2026-04-01`
- **Release name:** `Notification Polling Load Reduction`

## Summary

This release reduces production notification traffic by polling the lightweight unread-count endpoint while the header bell is closed and only fetching the full notifications feed when the drawer is open.

## Version Sources of Truth

- Root version file: `VERSION`
- Root package metadata: `package.json`
- Frontend package metadata: `fwber-frontend/package.json`
- Historical release notes: `CHANGELOG.md`
