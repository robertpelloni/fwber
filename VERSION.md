# Version Reference

- **Current version:** `1.0.25`
- **Release date:** `2026-04-01`
- **Release name:** `Recommendations Feed Schema Repair`

## Summary

This release repairs the recommendations feed event timestamp query so production no longer references the nonexistent `events.start_time` column, and it hardens key frontend pages to consume the shared API client's unwrapped responses correctly.

## Version Sources of Truth

- Root version file: `VERSION`
- Root package metadata: `package.json`
- Frontend package metadata: `fwber-frontend/package.json`
- Historical release notes: `CHANGELOG.md`
