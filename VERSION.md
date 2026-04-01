# Version Reference

- **Current version:** `1.0.10`
- **Release date:** `2026-04-01`
- **Release name:** `Final Route Prefetch Cleanup`

## Summary

This release disables the final remaining Link-prefetch sources for `/messages` and `/proximity` that were still surfacing noisy RSC fallback logs after the broader app-shell hardening.

## Version Sources of Truth

- Root version file: `VERSION`
- Root package metadata: `package.json`
- Frontend package metadata: `fwber-frontend/package.json`
- Historical release notes: `CHANGELOG.md`
