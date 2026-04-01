# Version Reference

- **Current version:** `1.0.11`
- **Release date:** `2026-04-01`
- **Release name:** `Auth Persistence Race Fix`

## Summary

This release fixes a frontend auth persistence race where freshly authenticated sessions could be cleared by immediate post-login API requests before the token had been persisted for the shared API client to use.

## Version Sources of Truth

- Root version file: `VERSION`
- Root package metadata: `package.json`
- Frontend package metadata: `fwber-frontend/package.json`
- Historical release notes: `CHANGELOG.md`
