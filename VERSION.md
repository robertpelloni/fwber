# Version Reference

- **Current version:** `1.0.20`
- **Release date:** `2026-04-01`
- **Release name:** `Notification Schema Memoization`

## Summary

This release memoizes notification schema detection within each request so the hot notifications path stops repeating the same cache-backed legacy-schema lookup during a single response cycle.

## Version Sources of Truth

- Root version file: `VERSION`
- Root package metadata: `package.json`
- Frontend package metadata: `fwber-frontend/package.json`
- Historical release notes: `CHANGELOG.md`
