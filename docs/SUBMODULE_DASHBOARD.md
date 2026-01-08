# Submodule Dashboard

## Overview
This dashboard tracks the status of Git submodules within the FWBER ecosystem.

**Current Status**: Monolithic Repository
**Last Updated**: 2026-01-08

## Active Submodules
*No active submodules configured.*

The project is currently structured as a monolithic repository containing both:
- `fwber-backend` (Laravel)
- `fwber-frontend` (Next.js)

## Submodule Strategy
If external components are extracted in the future (e.g., shared UI library, independent microservices), they will be listed here with their respective:
- Path
- Branch/Commit reference
- Sync status

## Maintenance
To check for submodule status in the future:
```bash
git submodule status
```
