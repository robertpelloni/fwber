# Test Plan

This document outlines the testing strategy for the "fwber" application, covering unit, feature, and end-to-end (E2E) testing.

## 1. Testing Pyramid Strategy

We follow a standard testing pyramid approach:

*   **Unit Tests (PHPUnit):** Focus on individual classes, models, and service logic in isolation. Fast execution.
*   **Feature Tests (PHPUnit):** Verify API endpoints, controller logic, database interactions, and integration between components.
*   **E2E Tests (Cypress/Playwright):** Simulate real user interactions in the browser, verifying the full stack from frontend to backend.

## 2. Backend Testing (Laravel/PHPUnit)

### Scope
*   **Models:** Data integrity, relationships, scopes, accessors/mutators.
*   **Controllers:** Request validation, response format, status codes, middleware authorization.
*   **Services:** Business logic, external API integrations (mocked), complex calculations.
*   **Events/Jobs:** Queue processing, event listeners.

### Key Test Suites
*   `Tests\Feature\Auth`: Authentication flows (login, register, wallet login).
*   `Tests\Feature\ChatroomTest`: Chat functionality, WebSocket broadcasting.
*   `Tests\Feature\MatchingTest`: Matching algorithms, swipe logic.
*   `Tests\Feature\PaymentTest`: Stripe integration (mocked), token economy.

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Auth/LoginTest.php

# Run tests with coverage
php artisan test --coverage
```

## 3. Frontend Testing (React/Cypress/Playwright)

### Scope
*   **Components:** Rendering, props, state management, interactivity.
*   **Pages:** Routing, data fetching, user flows.
*   **Integration:** API communication, WebSocket real-time updates.

### E2E Scenarios
*   **User Registration:** Sign up, profile creation, onboarding.
*   **Matching Flow:** Swiping, match notification, starting a chat.
*   **Chat:** Sending/receiving messages, real-time updates.
*   **Payments:** Wallet interactions, purchasing premium features.

### Verification Scripts (Playwright)
Located in `fwber-frontend/verification/`, these scripts provide quick visual verification of UI changes.
*   `verify_new_pages.py`: Checks new page routes.
*   `verify_viral_loop.py`: Validates viral features.
*   `verify_help_and_tooltips.py`: Checks UI documentation.

## 4. Performance Testing (k6)

### Scope
*   **Load Testing:** Simulating concurrent users to identify bottlenecks.
*   **Stress Testing:** Determining system breaking points.
*   **Endurance Testing:** Checking for memory leaks over time.

### Baseline
*   Script: `fwber-backend/scripts/perf/baseline_runner.py`
*   Target: `/api/health`, `/api/dashboard/stats`
*   Metric: Request latency, throughput (RPS), error rate.

## 5. Continuous Integration (CI)

*   **GitHub Actions:** Automatically runs unit and feature tests on every push/PR.
*   **Pre-commit Hooks:** Linting and static analysis before commit.

## 6. Manual Testing Checklist

*   [ ] **Visual Regression:** Check for layout shifts or broken styles.
*   [ ] **Cross-Browser:** Verify functionality on Chrome, Firefox, Safari.
*   [ ] **Mobile Responsiveness:** Ensure UI works on various screen sizes.
*   [ ] **Accessibility:** Check for ARIA labels and keyboard navigation.
