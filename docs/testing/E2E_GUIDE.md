# E2E Test Guide

## Overview
This project uses Cypress for End-to-End (E2E) testing. The tests simulate user behavior to verify critical flows like onboarding, matching, and messaging.

## Prerequisites
- Node.js (v18+)
- Backend server running on `http://localhost:8002`
- Frontend server running on `http://localhost:3000`
- Mercure hub running on `http://localhost:3001` (optional for mocks, but recommended for full integration)

## Running Tests

### 1. Interactive Mode
To run tests with the Cypress GUI (useful for debugging):
```bash
npx cypress open
```

### 2. Headless Mode
To run all tests in the terminal:
```bash
npx cypress run
```

To run a specific test file:
```bash
npx cypress run --spec "cypress/e2e/matching-flow.cy.js"
```

## Critical Flows Covered
- **Matching Flow**: `cypress/e2e/matching-flow.cy.js`
  - Verifies the match deck loads
  - Simulates swiping/liking
  - Checks for match confirmation (toast/modal)
- **Messaging Flow**: `cypress/e2e/messaging-flow.cy.js`
  - Verifies messaging after a match
  - Simulates real-time message updates via WebSocket mocks

## Troubleshooting
- **Tests timing out?** Ensure the local development servers are running.
- **Uncaught Exceptions?** Some third-party libraries (like Solana wallet adapter) might throw non-critical errors. These are handled in `cypress/support/e2e.js`.
