# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.99.6] - 2026-03-25

### Added
- Integrated Rust `fwber-geo` microservice into Laravel `LocationController` for high-performance spatial queries.
- Implemented `GeoScreenerService` in Laravel to communicate with the Rust backend.
- Created `ActivityPubSearchController` for federated user discovery via WebFinger.
- New **Scientific Nemesis** UI in the AI Wingman Arcade.
- New **Merchant Vibe Broadcast** UI for business partners.
- New **Business Intelligence** dashboard for merchants.
- "Global Federation" settings section for user opt-in and discovery.

### Fixed
- Resolved 500 Internal Server Errors caused by global exception handler swallowing 401/422 responses.
- Fixed `TypeError: includes is not a function` in frontend components by adding `Array.isArray()` guards.
- Corrected "Double API" and "Missing API" URL prefixing in `use-analytics.ts` and `client.ts`.
- Fixed CSS/JS asset blocking (`nosniff`) via `next.config.js` and `.htaccess` header consolidation.

### Changed
- Refactored `next.config.js` to use a unified headers management system.
- Moved `/api/location` out of `auth:sanctum` group temporarily for debugging (to be restored).
- Updated AI Agent instructions (`AGENTS.md`, `CLAUDE.md`, `GEMINI.md`, `GPT.md`) for autonomous operations.

## [0.99.1] - 2026-03-24
- Initial Gold Pre-release with Event Sourcing and AI Avatars.
