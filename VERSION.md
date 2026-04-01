# Version Reference

- **Current version:** `1.0.5`
- **Release date:** `2026-04-01`
- **Release name:** `Frontend Auth & Asset Recovery`

## Summary

This release hardens live frontend recovery by revalidating stored auth with `/api/auth/me` and clearing stale service-worker/cache state when old `_next` assets break after deployment.

## Version Sources of Truth

- Root version file: `VERSION`
- Root package metadata: `package.json`
- Frontend package metadata: `fwber-frontend/package.json`
- Historical release notes: `CHANGELOG.md`
