# Version Reference

- **Current version:** `1.0.24`
- **Release date:** `2026-04-01`
- **Release name:** `Sanctum Token Touch Throttle`

## Summary

This release throttles Sanctum `personal_access_tokens.last_used_at` writes so bearer-authenticated polling endpoints stop updating token usage on every single request while still preserving recent token activity data.

## Version Sources of Truth

- Root version file: `VERSION`
- Root package metadata: `package.json`
- Frontend package metadata: `fwber-frontend/package.json`
- Historical release notes: `CHANGELOG.md`
