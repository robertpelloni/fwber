# Version Reference

- **Current version:** `1.0.21`
- **Release date:** `2026-04-01`
- **Release name:** `Health Version Source Repair`

## Summary

This release makes the backend health endpoint report the real deployed application version by sourcing `config('app.version')` from the repository `VERSION` file instead of an ancient hardcoded fallback.

## Version Sources of Truth

- Root version file: `VERSION`
- Root package metadata: `package.json`
- Frontend package metadata: `fwber-frontend/package.json`
- Historical release notes: `CHANGELOG.md`
