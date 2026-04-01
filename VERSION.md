# Version Reference

- **Current version:** `1.0.12`
- **Release date:** `2026-04-01`
- **Release name:** `Auth Token Source Hardening`

## Summary

This release hardens the shared API client to use an in-memory auth token immediately after login, removing the dependency on `localStorage` timing for post-login authenticated requests.

## Version Sources of Truth

- Root version file: `VERSION`
- Root package metadata: `package.json`
- Frontend package metadata: `fwber-frontend/package.json`
- Historical release notes: `CHANGELOG.md`
