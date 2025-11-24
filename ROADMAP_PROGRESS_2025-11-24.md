# Roadmap Progress Report
**Date:** November 24, 2025
**Status:** In Progress

## Completed Tasks
1.  **Face Reveal Feature**:
    -   **E2E Testing**: Fixed `fwber-frontend/cypress/e2e/face-reveal.cy.js` by resolving CSS layout issues (0-height container) and updating assertions to match the "Match" tier logic (95% reveal).
    -   **Component Fixes**: Updated `FaceReveal.tsx` and `PhotoRevealGate.tsx` to ensure proper rendering in both browser and headless test environments.

2.  **Proximity Chat Discovery**:
    -   **Backend Optimization**: Updated `ProximityChatroomController::findNearby` to include an `is_member` boolean flag for each chatroom, efficiently checking user membership.
    -   **Frontend UX**: Updated `ProximityFeed.tsx` to display "Enter Room" (Green) for joined rooms and "Join Room" (Purple) for new rooms.
    -   **E2E Verification**: Updated `proximity-feed.cy.js` to verify the correct button states and navigation for both joined and unjoined rooms. Tests passed.

## Next Steps
-   **General**:
    -   Run full regression suite to ensure no side effects.

---
**Signed off by:** GitHub Copilot (Agent)
