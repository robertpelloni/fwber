# Changelog

All notable changes to this project will be documented in this file.

## [0.3.20] - 2026-01-01

### Security
- **Backend Hardening**: Implemented strict Content Security Policy (CSP) in `SecurityHeaders.php` (blocks unsafe-inline/eval in production).
- **CORS Restricted**: Tightened `config/cors.php` to disallow wildcard origins and only permit specific headers/methods.
- **Environment**: Updated `.env.example` to ensure safe defaults (APP_DEBUG=false, empty keys).

### Fixed
- **Frontend Build**: Disabled Turbopack in `next.config.js` to resolve build failures with custom webpack config.

## [0.3.19] - 2025-12-31

### Added
- **Solana Integration**: Full merge of Solana crypto features, including wallet connection and token management.
- **Vouch Leaderboard**: Added detailed breakdown (Safe/Fun/Hot) to the leaderboard API and UI.
- **Token-Gated Events**: Implemented backend logic and frontend UI for events requiring FWB tokens for entry.
- **Media Analysis**: Verified AWS Rekognition and OpenAI Vision drivers are in place for content safety.

### Fixed
- **Database Schema**: Confirmed `token_cost` column exists in `events` table via migration.
- **Event Creation**: ensured `token_cost` is properly handled in `StoreEventRequest` and `EventController`.

## [0.3.18] - 2025-12-30

### Fixed
- **Deployment**: Resolved memory allocation failure during build process by increasing Node memory limit.
- **Dependencies**: Successfully synced and verified dependency installation in `fwber-frontend`.

## [0.3.17] - 2025-12-30

### Fixed
- **Testing**: Added `cypress/e2e/match-insights.cy.js` to verify the AI Match Insights unlock flow.
- **Testing**: Fixed selectors in `matching-flow.cy.js` to correctly target the "Like" button, resolving previous test failures.
- **Development**: Improved `lib/echo.ts` to handle WebSocket fallback configuration in test/dev environments, reducing console noise.
- **Frontend**: Fixed `ProfileViewModal` to correctly update state after unlocking AI Insights, ensuring the UI refreshes without a page reload.

## [0.3.3] - 2025-12-28
...