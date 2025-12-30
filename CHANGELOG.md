# Changelog

All notable changes to this project will be documented in this file.

## [0.3.17] - 2025-12-30

### Fixed
- **Testing**: Added `cypress/e2e/match-insights.cy.js` to verify the AI Match Insights unlock flow.
- **Testing**: Fixed selectors in `matching-flow.cy.js` to correctly target the "Like" button, resolving previous test failures.
- **Development**: Improved `lib/echo.ts` to handle WebSocket fallback configuration in test/dev environments, reducing console noise.
- **Frontend**: Fixed `ProfileViewModal` to correctly update state after unlocking AI Insights, ensuring the UI refreshes without a page reload.

## [0.3.3] - 2025-12-28
...