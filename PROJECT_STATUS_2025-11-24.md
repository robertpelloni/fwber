# Project Status & Roadmap (November 26, 2025)

## 🟢 Current Status: MVP Complete + Advanced Features
The project has successfully delivered the core MVP scope and has significantly advanced into secondary feature sets. The application is feature-complete for a robust beta release.

### ✅ Completed MVP Features
1.  **Authentication**: Secure registration, login, and session management.
2.  **Profile Management**: Comprehensive profile editing, including "Physical Profile" attributes.
3.  **Dashboard**: User activity overview and stats.
4.  **Matches**: Matching logic, listing, and interaction.
5.  **Direct Messages**: Real-time messaging between matched users.
6.  **Photos**: Upload, management, and secure storage.
7.  **Safety & Moderation**:
    -   Block/Report functionality.
    -   Moderation Dashboard (Admin).
    -   Geo-spoofing detection and shadow throttling.
8.  **Location Services**: Privacy-first location updates and "Nearby" discovery.
9.  **Proximity Artifacts (Local Pulse)**: Ephemeral, location-based content feed.

### ✅ Completed Secondary Systems
1.  **AI Content Generation**:
    -   Profile bio generation.
    -   Conversation starters.
    -   Bulletin board post suggestions.
2.  **Recommendations Engine**:
    -   AI-powered, location-based, and collaborative filtering recommendations.
    -   Trending content feed.
3.  **Proximity Chatrooms**:
    -   Location-based chat rooms with "enter/leave" mechanics.
    -   Real-time messaging within rooms.
4.  **Face Reveal**:
    -   Progressive photo reveal mechanics based on relationship tiers.
    -   Frontend UI integrated with feature flag gating (`NEXT_PUBLIC_FEATURE_FACE_REVEAL`).
    -   `PhotoRevealGate`, `FaceReveal`, and `SecurePhotoReveal` components ready.
5.  **Admin Observability (Nov 26)**:
    -   Analytics dashboard wired to backend (`/analytics`, `/analytics/realtime`, `/analytics/moderation`).
    -   Rate Limit Stats component fixed to use proper API client with `NEXT_PUBLIC_API_URL`.
    -   CSV export functionality added for analytics and rate limit data.
    -   Frontend feature flag hooks (`use-feature-flags.ts`) for runtime feature gating.

### 🚧 In Progress / Next Steps
1.  **WebSocket/Mercure Integration**:
    -   *Status*: **Complete**. Refactored frontend to use `WebSocketProvider` for singleton connection.
    -   *Verification*: E2E tests (`realtime-chat.cy.js`) passing for connection, messaging, typing indicators, and presence.
2.  **Analytics & Rate Limiting**:
    -   *Status*: **Complete**. Backend routes implemented; frontend admin views now properly connected.
    -   *CSV Export*: Full analytics report export available.
3.  **Generic Chatrooms**:
    -   *Status*: Implemented alongside Proximity Chatrooms.
    -   *Next*: Verify distinction and specific use cases vs Proximity rooms.
4.  **Admin Feature Flag Toggle UI**:
    -   *Status*: Pending. Need to implement admin UI for runtime feature flag management.
5.  **Local Media Vault**:
    -   *Status*: Backend spike complete. Frontend UI pending (`NEXT_PUBLIC_FEATURE_LOCAL_MEDIA_VAULT`).

## 📂 Project Structure Overview
-   **`fwber-backend/`**: Laravel 12 API.
    -   Routes: `routes/api.php`
    -   Features: `config/features.php`
-   **`fwber-frontend/`**: Next.js 14 App.
    -   Pages: `app/`
    -   Components: `components/`
    -   Hooks: `lib/hooks/` (includes `use-feature-flags.ts`, `use-admin-analytics.ts`)
    -   Utils: `lib/utils/csv-export.ts` for analytics export
    -   Tests: `cypress/e2e/`
-   **`docs/`**: Documentation.
    -   `AGENTS.md`: Operational guide for AI agents.
    -   `API_DOCS.md`: API reference.
    -   `FEATURE_FLAGS.md`: Feature flag management.
    -   `docs/roadmap/ROADMAP_REFRESH_2025-11-25.md`: Latest roadmap priorities.

## 🛠 Maintenance & Cleanup
-   **Documentation**: Consolidated into this file and `AGENTS.md`. Old status reports archived.
-   **Testing**: Full E2E suite available in `fwber-frontend/cypress/e2e/`.
-   **Feature Flags**: See `docs/FEATURE_FLAGS.md` for complete list and configuration.
