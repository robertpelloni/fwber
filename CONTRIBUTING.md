# Contributing to fwber

## 🚀 Project Overview
fwber is a comprehensive social networking platform built with **Laravel 12 (API)** and **Next.js 14 (Frontend)**. We are currently in the **Post-Launch Monitoring & Growth** phase.

## 🛠 Tech Stack
- **Backend**: Laravel 12, PHP 8.2+, MySQL 8, Redis.
- **Frontend**: Next.js 14 (App Router), TypeScript, Tailwind CSS, Shadcn UI.
- **Real-time**: Mercure Hub, WebSocket.
- **Testing**: PHPUnit (Backend), Cypress (E2E).

## 📦 Versioning & Changelog
**Rule: Every build/task completion must have a new version number.**

### 1. Determine the Version Bump
We follow [Semantic Versioning](https://semver.org/):
- **Patch (`x.x.Z`)**: Bug fixes, small tweaks, refactors, config changes.
- **Minor (`x.Y.x`)**: New features, new routes, significant UI changes.
- **Major (`X.x.x`)**: Breaking changes to the API or core architecture.

### 2. Update Version Numbers
Update the version in the following files:
1.  `package.json` (Root)
2.  `fwber-frontend/package.json`
3.  `fwber-backend/composer.json` (optional, but good practice)

### 3. Update Changelog
Add an entry to `CHANGELOG.md` under the new version number.
- Format: `## [Version] - YYYY-MM-DD`
- Categories: `### Added`, `### Changed`, `### Fixed`, `### Removed`.

## 🧪 Testing Standards
- **Backend**: Run `php artisan test` for unit/feature tests.
- **Frontend**: Run `npm run lint` and `npm run type-check`.
- **E2E**: Run `npx cypress run` for critical flows.

## 📝 Workflow
1.  **Check Status**: Read `PROJECT_STATUS.md`.
2.  **Plan**: Create a checklist.
3.  **Implement**: Write code.
4.  **Verify**: Run tests.
5.  **Version**: Bump version & update Changelog.
6.  **Document**: Update `PROJECT_STATUS.md`.
