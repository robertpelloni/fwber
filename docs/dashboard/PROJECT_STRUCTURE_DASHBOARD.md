# Project Structure Dashboard

**Last Updated:** 2026-01-08
**Version:** v0.3.24 (Target)

## OVERVIEW
`fwber` is a monolithic repository containing both the backend API and frontend application.
**Note:** `fwber-backend` and `fwber-frontend` are standard directories within this repository, NOT git submodules.

## DIRECTORY LAYOUT

### 📂 Root (`/`)
| Path | Description |
|------|-------------|
| `fwber-backend/` | Laravel 12 API & Application Logic |
| `fwber-frontend/` | Next.js 14 Web Application |
| `docker/` | Docker Compose & Container Configurations |
| `docs/` | Project Documentation & Knowledge Base |
| `AGENTS.md` | Master Context Index for AI Agents |
| `VERSION` | Current Project Version |

### 🐘 Backend (`fwber-backend/`)
**Stack:** Laravel 12, PHP 8.4, MySQL 8
| Key Path | Purpose | Context File |
|----------|---------|--------------|
| `app/` | Core Application Code | `AGENTS.md` (Root) |
| `database/migrations/` | Database Schema Definitions | [`database/migrations/AGENTS.md`](../../fwber-backend/database/migrations/AGENTS.md) |
| `routes/` | API & Web Routes | |
| `tests/` | PHPUnit / Pest Tests | |

### ⚛️ Frontend (`fwber-frontend/`)
**Stack:** Next.js 14, React, Tailwind, Shadcn UI
| Key Path | Purpose | Context File |
|----------|---------|--------------|
| `app/` | App Router (Pages, Layouts) | [`app/AGENTS.md`](../../fwber-frontend/app/AGENTS.md) |
| `components/` | Reusable UI Components | [`components/AGENTS.md`](../../fwber-frontend/components/AGENTS.md) |
| `lib/` | Utilities & Helper Functions | |
| `public/` | Static Assets | |

## INFRASTRUCTURE
- **Docker:** Development environment defined in `docker-compose.yml` (if present at root) or `docker/` configs.
- **CI/CD:** GitHub Actions (workflows in `.github/workflows/`).

## DOCUMENTATION MAP
- **Master Protocol:** [`docs/UNIVERSAL_LLM_INSTRUCTIONS.md`](../UNIVERSAL_LLM_INSTRUCTIONS.md)
- **Status:** [`docs/PROJECT_STATUS.md`](../PROJECT_STATUS.md)
- **Changelog:** [`CHANGELOG.md`](../../CHANGELOG.md)
