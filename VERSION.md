# Version Reference

- **Current version:** `1.0.7`
- **Release date:** `2026-04-01`
- **Release name:** `Dashboard Auth Query Cleanup`

## Summary

This release removes the remaining dashboard-era direct frontend API calls that were bypassing the shared authenticated `/api` client, and gates protected widgets so they do not fire unauthenticated requests during auth initialization.

## Version Sources of Truth

- Root version file: `VERSION`
- Root package metadata: `package.json`
- Frontend package metadata: `fwber-frontend/package.json`
- Historical release notes: `CHANGELOG.md`
