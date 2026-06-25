# Submodule Dashboard — fwber

> **Last Updated:** 2026-06-23
> **Root Version:** 2.3.8

---

## Topology

| Component | Path | Type | Remote | Current Commit | Status |
|-----------|------|------|--------|---------------|--------|
| **Root** | `.` | Monorepo | `github.com/robertpelloni/fwber` | `d5addb6c1` | ✅ Active |
| **Backend (TS)** | `fwber-backend-ts/` | Directory | (same repo) | — | ✅ Active, port 4003 |
| **Frontend** | `fwber-frontend/` | Directory | (same repo) | — | ✅ Active, Vercel |
| **Geo Service** | `fwber-geo/` | Directory | (same repo) | — | ✅ Active, port 8081 |

## Change Log (Recent)

| Commit | Date | Description |
|--------|------|-------------|
| `d5addb6c1` | 2026-06-23 | chore: move backend API from port 4002 to 4003 |
| `b681e789b` | 2026-06-23 | feat: 10,000x shiny — particles, premium effects, floating 3D shapes, quests bugfix |
| `0f9d61c74` | 2026-06-22 | feat: final UI polish — refined glassmorphism, animations, cleanup |
| `b7e45b737` | 2026-06-22 | feat: 1000x shiny UI overhaul — glassmorphism, framer-motion animations, premium design system |
| `ac36a75d7` | 2026-06-22 | feat: forward merge continue-development — email infrastructure docs, API crash fixes, code cleanup |

## Port Registry

| Port | Service | Manager |
|------|---------|---------|
| 4003 | fwber-backend-ts | PM2 |
| 8081 | fwber-geo | systemd |
| 4000 | freellm (unrelated) | process |
| 8080 | hustle (unrelated) | process |
| 8082 | orchestrator (unrelated) | process |
| 8083 | python3 (unrelated) | process |
| 8086 | sales_bot (unrelated) | process |

## Deploy Targets

| Component | Target | Method |
|-----------|--------|--------|
| Frontend | Vercel (`fwber.me`) | Auto-deploy from GitHub push |
| Backend (TS) | Hetzner VPS `5.161.250.43:4003` | `ops/hetzner/scripts/deploy-backend-ts.sh` |
| Geo (Rust) | Hetzner VPS `5.161.250.43:8081` | systemd `fwber-geo.service` |
| Database | MySQL on Hetzner | Localhost |
