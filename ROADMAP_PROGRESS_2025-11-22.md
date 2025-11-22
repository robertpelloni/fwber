# Roadmap Progress Report
**Date:** November 22, 2025
**Status:** In Progress

## Completed Tasks
1.  **Client-Side Face Blurring Enhancements**:
    -   **Hardened Caching**: Implemented `cleanupOldCaches` in `fwber-frontend/lib/faceBlur.ts` to manage model versions and prevent storage bloat.
    -   **Telemetry**: Added `skippedReason` to `PreviewDiscardedPayload` in `fwber-frontend/lib/previewTelemetry.ts` and updated `PhotoUpload.tsx` to track why uploads are skipped (e.g., no faces detected).

2.  **Face Reveal Game**:
    -   **Component**: Created `fwber-frontend/components/FaceReveal.tsx` which implements a progressive unblur effect with a visual progress bar and lock icon.
    -   **Integration**: Updated `fwber-frontend/components/PhotoRevealGate.tsx` to use `FaceReveal` for "Blurred Real Photos", calculating reveal progress based on messages exchanged in the `MATCHED` tier.

3.  **Proximity Chat Discovery**:
    -   **API**: Added `findNearbyChatrooms` to `fwber-frontend/lib/api/proximity.ts` and defined `ProximityChatroom` type.
    -   **UI**: Updated `fwber-frontend/components/ProximityFeed.tsx` to fetch and display nearby chatrooms in a dedicated section above the feed.

## Next Steps
-   **Testing**: Verify the "Face Reveal" progression in a live environment (requires simulating message exchange).
-   **Backend**: Ensure `ProximityChatroomController` endpoints are fully functional and optimized for the new discovery UI.
-   **Refinement**: Consider adding a "Join" button directly in the Proximity Feed chatroom cards.

---
**Signed off by:** GitHub Copilot (Agent)
