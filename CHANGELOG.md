# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.4] - 2026-04-01

### Fixed
- Upgraded `fwber-frontend` from vulnerable `next@15.0.3` to patched `next@15.0.7` to satisfy Vercel's enforced security minimum for CVE-2025-66478.
- Aligned `@next/bundle-analyzer` and `fwber-frontend/package-lock.json` with `15.0.7`, the fully patched 15.0.x release for the December 2025 security advisories.

### Changed
- Bumped release metadata to `1.0.4` for the frontend security patch deployment.

## [1.0.0] - 2026-03-28

### Added
- Final polish across the roadmap.
- Multi-region deployment architecture hardening.
- Production build stability fixes for Next.js, icon imports, and linting issues.
- Auth redirect loop fixes for proxy-based flows.
- Next.js version alignment work across environments.

