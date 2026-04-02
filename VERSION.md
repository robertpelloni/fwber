# Version Reference

- **Current version:** `1.0.72`
- **Release date:** `2026-04-02`
- **Release name:** `Production 500 Endpoint Hardening`

## Summary

This release hardens the backend against the most plausible DreamHost-only 500s called out in `TODO.md`: location updates now survive event-store outages, photo listing no longer crashes on null storage paths, and safety reads degrade cleanly when the optional safety tables are missing.

## Version Sources of Truth

- Root version file: `VERSION`
- Root package metadata: `package.json`
- Frontend package metadata: `fwber-frontend/package.json`
- Historical release notes: `CHANGELOG.md`
