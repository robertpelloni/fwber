# Version Reference

- **Current version:** `1.0.22`
- **Release date:** `2026-04-01`
- **Release name:** `Tagged Cache Runtime Fallback`

## Summary

This release hardens the shared tagged-cache helper so production endpoints keep working even when the active cache store advertises tag support but throws at runtime, restoring `/api/matches` safety on DreamHost.

## Version Sources of Truth

- Root version file: `VERSION`
- Root package metadata: `package.json`
- Frontend package metadata: `fwber-frontend/package.json`
- Historical release notes: `CHANGELOG.md`
