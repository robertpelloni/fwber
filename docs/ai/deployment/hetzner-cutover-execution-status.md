# Hetzner Cutover Execution Status

**Date:** 2026-04-04  
**Version:** 1.5.4

## Scope
This note captures the real Hetzner execution work completed during the live server session.

## Completed on Hetzner (`5.161.250.43`)
- cloned the `fwber` repo into `/var/www/fwber/repo`
- installed missing deployment dependencies required for the active stack:
  - `php8.4-sqlite3`
  - `cargo`
  - `rustc`
  - `sshpass`
- installed modern Rust via `rustup` for the `fwber-geo` `edition2024` build requirement
- built `fwber-geo` release successfully
- created a local MySQL database/user for fwber
- imported the DreamHost fwber production database into Hetzner MySQL
- reconfigured the Hetzner backend runtime to use:
  - local MySQL
  - local Redis
  - Reverb
  - S3 media storage
- installed and enabled systemd units for:
  - `fwber-queue`
  - `fwber-reverb`
  - `fwber-geo`
- verified Redis, Reverb, queue worker, and geo service are running
- verified websocket upgrade success through nginx using the production-style Reverb app key
- verified `php artisan deploy:verify --json` returns healthy on Hetzner

## Current Remaining External Cutover Work
The application runtime is now deployed on Hetzner, but full public cutover still depends on external changes:
- repoint `api.fwber.me` DNS to Hetzner
- repoint `geo.fwber.me` DNS to Hetzner
- issue/confirm TLS certificates for `api.fwber.me` and `geo.fwber.me` on Hetzner after DNS points correctly
- optionally retire the DreamHost fwber backend once cutover is validated

## Important Live Findings
- DreamHost MySQL was not directly usable from Hetzner via the public hostname in the current setup, so migrating the fwber DB locally to Hetzner was the correct simplification move.
- `ws.fwber.me` is already working correctly on Hetzner with a successful websocket handshake.
- `geo.fwber.me` and `api.fwber.me` still require final public DNS/TLS cutover to fully complete the migration.
