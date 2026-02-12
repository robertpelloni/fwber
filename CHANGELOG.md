# Changelog

All notable changes to this project will be documented in this file.

## [0.3.34] - 2026-02-06

### Added
- **Merchant Analytics Integration**: Connected the `/merchant/analytics` frontend page to the real backend endpoint `/api/merchant-portal/analytics`, replacing mock data with "estimated" metrics from the `MerchantAnalyticsService`.
- **System Dashboard Integration**: Connected the `/admin/system` frontend page to the real backend health check endpoint `/api/health`, providing actual system status, version, and database connectivity info.
- **Documentation**: Added `docs/dashboard/SUBMODULE_VERSIONS.md` to track logical module versions in the monorepo.

### Changed
- **System Health API**: Enhanced `HealthController` to return detailed checks for database, cache, and storage, consumed by the new System Dashboard.

## [0.3.33] - 2026-02-06

### Added
- **System Dashboard**: New `/admin/system` page providing a high-level overview of project structure, submodule status, backend health, and version information.
- **Frontend Feature Completion**:
    - **Achievements UI**: Full implementation of gamification tracking at `/achievements`.
    - **Help Center**: Comprehensive user guide and documentation portal at `/help`.
    - **Security Settings**: End-to-end encryption key management at `/settings/security`.
- **Refactoring**: Unified real-time logic into `useWebSocket`, replacing legacy Mercure hooks.
- **Polish**: Resolved extensive linting errors and build warnings across the frontend codebase.

## [0.3.31] - 2026-02-05

### Added
- **Documentation**: Created `VISION.md` to articulate the project's long-term philosophy and design pillars.
- **LLM Instructions**: Consolidated AI agent protocols into `docs/UNIVERSAL_LLM_INSTRUCTIONS.md` and created aligned `CLAUDE.md` and `copilot-instructions.md`.
- **Merchant Analytics Backend**:  Created `MerchantAnalyticsController` and `MerchantAnalyticsService` to power the merchant dashboard (currently serving mock data).

### Analysis
- Confirmed Phase 4B feature completeness.
- Identified frontend-backend disconnect in Merchant Analytics as the next immediate technical debt to resolve.

## [0.3.30] - 2026-01-12

### Added
- **Expanded Onboarding Flow**: Added 4 new onboarding steps (Physical, Lifestyle, Personality, Intimate) to collect comprehensive profile data during signup.
- **Profile Physical Tab**: New Physical tab in profile editor for height, body type, ethnicity, hair/eye color, skin tone, facial hair, fitness level, tattoos, piercings.
- **Profile Intimate Tab**: New Intimate tab in profile editor for breast size, penis dimensions, STI status, fetishes/kinks.

### Fixed
- **Looking For Checkboxes**: Fixed checkbox styling in onboarding and profile pages - checkboxes now properly respond to clicks with visible styling (h-5 w-5, accent colors, cursor-pointer).

### Changed
- **ProfileUpdateData Type**: Extended API type to include all physical/intimate fields (height_cm, body_type, breast_size, penis_length_cm, penis_girth_cm, sti_status, fetishes, etc.).

## [0.3.29] - 2026-01-11

### Added
- **Premium Unlocks Page**: New `/premium/unlocks` page for content unlock system - view unlocked insights, photos, and profiles with token costs.
- **Gift History Page**: New `/gifts/history` page with animated gift tracking - sent and received gifts, gift type icons, custom messages.
- **Merchant Analytics Page**: New `/merchant/analytics` page with K-Factor tracking, retention metrics (Day 1/7/30), top promotions table, revenue stats.

### Documentation
- Updated `docs/MISSING_FEATURES.md` - marked Content Unlock System, Gift System Enhancement, and Merchant Analytics as complete.

## [0.3.28] - 2026-01-09

### Added
- **Deals Page**: New `/deals` page for consumer deals and local merchant promotions discovery with location-based filtering.
- **Bounties Page**: New `/bounties` page for matchmaker bounties - browse bounties, filter by reward amount, see bounty details.
- **Wingman Fortune Page**: New `/wingman/fortune` page for AI-generated dating fortune predictions with mystical theming.
- **Wingman Cosmic Match Page**: New `/wingman/cosmic` page for zodiac-based compatibility analysis.
- **Wingman Vibe Check Page**: New `/wingman/vibe` page for green/red flag analysis of user profiles.
- **Wingman Roast Page**: New `/wingman/roast` page for AI-generated profile roasts and hype modes.
- **Wingman Nemesis Page**: New `/wingman/nemesis` page for discovering dating nemesis personality types.
- **Photo Reveals Page**: New `/photos/reveals` page showing all photos user has unlocked from matches.
- **Share-to-Unlock Page**: New `/share-unlock` page with social sharing incentives and reward tracking.
- **Group Matching Page**: New `/groups/matching` page for group-to-group matching with compatibility scores.

### Frontend
- All new pages follow consistent dark purple/pink gradient theme
- Full TypeScript compliance with strict type checking
- Responsive design for mobile and desktop

## [0.3.27] - 2026-01-09

### Added
- **Achievements Page**: New `/achievements` page with full gamification UI - category tabs (onboarding, social, activity, growth, viral), progress tracking, token earnings display, and visual distinction for locked/unlocked achievements.
- **Profile Views Page**: New `/profile-views` page ("Who Viewed You") - stats cards (total/today/week/unique views), viewer list with avatars and timestamps, tips section for increasing visibility.

### Fixed
- **Documentation Accuracy**: Corrected `docs/MISSING_FEATURES.md` - several features previously listed as missing were found to have complete implementations (PhotoRevealGate, ShareToUnlock, BulletinBoards, ProximityChatrooms, GroupMatching).

## [0.3.26] - 2026-01-09

### Added
- **Documentation**: Created `docs/DEVELOPMENT_MASTER_PLAN.md` - comprehensive reference document consolidating all codebase analysis findings.

### Updated
- **PROJECT_STATUS.md**: Updated architecture details (78 controllers, 53 services, 200+ tests), version info, and immediate roadmap.
- **ROADMAP.md**: Expanded Phase 4 with detailed Merchant Integration and Analytics Dashboard subtasks; added Phase 5 planning.

### Analysis Summary
- Completed exhaustive codebase discovery via parallel background agents
- Mapped 78 backend controllers, 53 services
- Cataloged 18+ frontend pages, 150+ components
- Verified 200+ test files with comprehensive coverage
- Confirmed codebase is clean (no TODO/FIXME/HACK comments)
- Documented full development history (Dec 2025 - Jan 2026)

## [0.3.25] - 2026-01-08

### Fixed
- **Photo Uploads**: Added safety check for `exif` extension in `PhotoController`. Prevents 500 error crashes on servers where the extension is missing by skipping image auto-orientation.

### Documentation
- **Submodules**: Created `docs/SUBMODULE_DASHBOARD.md` to document monolithic state.

## [0.3.24] - 2026-01-08

### Maintenance
- **Documentation**: Generated specialized `AGENTS.md` files for backend migrations, frontend components, and app router.
- **Structure**: Created `docs/dashboard/PROJECT_STRUCTURE_DASHBOARD.md` to map the monorepo layout.
- **Cleanup**: Verified standard directory structure and standardized versioning.

## [0.3.23] - 2026-01-07

### Fixed
- **Image Processing**: Fixed silent failure in image rotation fallback by properly handling errors and increasing memory limit to prevent OOM.
- **Frontend Assets**: Disabled experimental CSS optimization to resolve MIME type mismatch errors on CSS files.
- **Realtime Connection**: Corrected Reverb connection logic in production to properly use self-hosted instance instead of defaulting to public Pusher.

## [0.3.22] - 2026-01-06

### Fixed
- **Build Stability**: Resolved critical TypeScript errors in `LocationPicker` by stabilizing `react-leaflet` version to 4.2.1 and removing conflicting custom type definitions.

### Added
- **Local Pulse**: Enhanced the feed to support "Merchant Promotions" with distinct "Sponsored" styling, including discount badges and merchant names.

## [0.3.21] - 2026-01-05

### Added
- **AR View**: Implemented a lightweight Augmented Reality view for Local Pulse using standard Web APIs.
- **Geolocation Utilities**: Added helper functions for calculating distance and bearing.

### Fixed
- **Local Pulse UI**: Integrated AR Toggle button into the main Local Pulse interface.

## [0.3.20] - 2026-01-05

### Added
- **Local Face Models**: Moved face-api.js models to `public/models/` for reliable offline/non-CDN access.
- **Onboarding UX**: Improved photo upload progress bar with bounce/pulse animations for better feedback.
- **Dashboard**: Created `docs/dashboard/PROJECT_STRUCTURE_DASHBOARD.md` to track project structure and versions.

### Fixed
- **Photo Upload**: Corrected the rotation of the loading spinner icon (mirrored) to match the clockwise animation direction.
- **Progress Stability**: Fixed "bouncing" progress bar behavior where the state would reset incorrectly during multiple file uploads.
- **Feature Merges**: Merged `feature/event-discussions`, `feature/group-matching-shared-events`, and `feature/shared-event-invitations` into main.

### Security
- **Backend Hardening**: Implemented strict Content Security Policy (CSP) in `SecurityHeaders.php`.
- **CORS Restricted**: Tightened `config/cors.php`.
