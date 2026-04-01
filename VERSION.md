# Version Reference

- **Current version:** `1.0.17`
- **Release date:** `2026-04-01`
- **Release name:** `Auth Restore Network Fallback`

## Summary

This release closes the remaining frontend auth-restore gap so browser network errors during `/auth/me` restoration fall back to cached session state instead of clearing a valid login.

## Version Sources of Truth

- Root version file: `VERSION`
- Root package metadata: `package.json`
- Frontend package metadata: `fwber-frontend/package.json`
- Historical release notes: `CHANGELOG.md`
