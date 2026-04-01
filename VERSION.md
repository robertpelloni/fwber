# Version Reference

- **Current version:** `1.0.14`
- **Release date:** `2026-04-01`
- **Release name:** `Notifications Payload Hardening`

## Summary

This release hardens the backend `/notifications` endpoint so malformed stored notification payloads no longer crash authenticated notification fetches, preserving the feed even when individual rows are bad.

## Version Sources of Truth

- Root version file: `VERSION`
- Root package metadata: `package.json`
- Frontend package metadata: `fwber-frontend/package.json`
- Historical release notes: `CHANGELOG.md`
