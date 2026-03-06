oProceedrocddPro# Changelog

All notable changes to this project will be documented in this file.

## [1.3.2] - 2026-03-06

### Added
- **Evolving AI Avatars (Vibe-Aware)**: Implemented dynamic avatar modifiers based on the user's recent Local Pulse text posts.
  - Backend: `AvatarGenerationService` automatically analyzes the sentiment of the last 5 posts to inject mood/vibe descriptions into DALL-E prompts.
  - Generates robust fallback configurations ensuring stable prompts if users lack sufficient textual posting history.
  - Includes robust SQLite testing via `ProximityArtifactFactory`.
  - Frontend: Interactive profile settings display instructing users on the dynamic lighting and expression linkages.
  
## [1.3.1] - 2026-03-05

### Added
- **AI Date Planner API**: Implemented the backend endpoint for the frontend's Date Planner feature. The `AIMatchingService` now uses OpenAI (`gpt-4o-mini`) to generate customized date itineraries based on matched users' profiles and an optional target location. Ensure users can only generate ideas for valid matches.
- **Burner Communication Bridge**: Implemented the complete frontend interface for the privacy-first Burner Link feature. Users can now generate a scannable QR code & expiring link at `/burner`. Scanning a link directs a connecting user to `/burner/join/[token]` where an ephemeral 24-hour chatroom is automatically established between both parties.

## [1.3.0] - 2026-03-05

### Added
- **Panic Button & Safe Walk UI**: Implemented the frontend interface for the new safety features.
  - New `SafetyPage` (`/safety`) dashboard for managing Emergency Contacts and triggering Panic alerts.
  - Global `SafeWalkTracker` component injected into the root layout for persistent background location tracking during an active Safe Walk.
  - Integration with the pre-existing backend safety API endpoints (v1.0.5).

## [1.2.0] - 2026-03-06
### Added
- **Chemistry Score Post-Date Feedback Loop**: Closed-loop learning system for AI-powered match improvement.
  - Backend: `DateFeedback` model & migration with `match_id`, `reporting_user_id`, `subject_user_id`, `rating` (1-5), `feedback_text`, and `safety_concerns` fields.
  - Backend: `DateFeedbackController` with `store` (submit) and `show` (check existing) actions.
  - Backend: `AIMatchingService.calculateDateFeedbackModifier()` — adjusts compatibility scores ±15 points based on historical date ratings with similar personality types.
  - Backend: `DateFeedbackTest.php` — 3 feature tests (10 assertions) covering submission, duplicate prevention, and retrieval.
  - Frontend: `DateFeedbackModal` component with interactive star rating, private notes, and safety concern toggle.
  - Frontend: API bindings `submitDateFeedback()` and `checkDateFeedback()` in `lib/api/matches.ts`.
  - Frontend: "Rate Date" star button integrated into the `RealTimeChat` header for established matches.
### Fixed
- Fixed default import error for `api` in `app/settings/security/page.tsx` (changed to named import).

## [1.1.0] - 2026-03-05
### Added
- **Decoy Profile Mode**: Added a "Decoy Password" feature to the Security Settings.
- If a user is coerced into logging in, they can use their Decoy Password to seamlessly log into a completely separate, plausible dummy profile rather than revealing their actual account.
- Backend: Handled securely in the `AuthController` login flow, checking `isDecoyAuth` and routing to the linked `decoy_user_id`.
- Decoy Setup: Creates a secondary user account and dummy profile data transparently for plausible deniability.

## [1.0.9] - 2026-03-01
### Added
- **Audio Rooms Real-Time Engagement**: Fully functional "Clubhouse-style" mesh WebRTC networking.
- Backend: `AudioRoomSignal`, `AudioRoomParticipantJoined`, and `AudioRoomParticipantLeft` broadcasting events via Laravel Echo.
- Frontend: `useWebRTC` hook expanded to support dynamic multi-peer mesh connections.
- UI: Real-time "Raise Hand" request feature and presence syncing across audience and speakers.

## [1.0.8] - 2026-03-01
### Added
- **Wingman Arcade**: Created a centralized cyberpunk-themed hub (`/wingman`) for all AI-driven viral games.
- Navigation integration across sub-features (Fortune, Cosmic Match, Roast, Vibe Check, Nemesis).
- Added Arcade to global `AppHeader` navigation.

## [1.0.7] - 2026-02-28
### Added
- **Relationship Accelerator**: Integrated Ice Breakers and Scrapbook into the core chat loop.
- Proactive AI Wingman: Upgraded with specialized nudge categories (`ice_breaker`, `scrapbook`, `ask_out`, `back_off`).
- Dynamic Chat Links: Direct navigation to shared experiences from the chat header and AI suggestions.
- Backend: Enhanced `AiWingmanService` context analysis and `AnalyzeConversationNudge` background job.

## [1.0.6] - 2026-02-28
### Added
- **Voice Profile Introductions**: 30-second audio snippets for personalizing user profiles.
- `VoiceIntroRecorder` component for recording, previewing, and trimming audio.
- Backend support: `voice_intro_url` field in `UserProfile`, media storage integration, and validation rules.
- Profile integration: Audio playback in `Bio` component and upload management in `ProfilePage`.

## [1.0.5] - 2026-02-28
### Added
- **Panic Button & Safe Walk**: One-tap emergency alerts and live location-sharing during walks (`/safety`).
- Backend: `EmergencyContact` and `SafeWalk` models, migrations, and `SafetyController` (7 endpoints).
- Safety features: SOS panic trigger (notifies contacts), Safe Walk mode (destination tracking), and contact management.
- Integrated original `blockUser` and `reportUser` functions into a merged `lib/api/safety.ts`.
- Added "Safety" nav link with `Shield` icon to `AppHeader.tsx`.

## [1.0.4] - 2026-02-28
### Added
- **Scrapbook Mode**: Shared private memory board between matched users (`/scrapbook?match={id}`).
- Backend: `ScrapbookEntry` model, migration, `ScrapbookController` (index, store, togglePin, destroy).
- 4 API routes: `GET/POST /scrapbook`, `PATCH /scrapbook/{id}/pin`, `DELETE /scrapbook/{id}`.
- Masonry grid frontend with color-coded entry cards, type badges (text/image/voice/link), pin/delete controls.
- FAB button + modal form with type selector, 7-color accent picker, and emoji input.

## [1.0.3] - 2026-02-28
### Added
- **AI Date Planner**: Personalized date itinerary generator using the existing AI Wingman backend (`/date-planner`).
- Vertical timeline UI with color-coded step cards, venue/cost/duration metadata, and expandable conversation starters.
- Geolocation auto-fill for location-aware AI suggestions.
- `useDateIdeas` React Query hook and `date-planner.ts` API client.
- Added "Date Planner" nav link with `CalendarHeart` icon to `AppHeader.tsx`.

## [1.0.2] - 2026-02-28
### Added
- **Ice Breaker Cards**: Structured AMA-style question cards for first-contact gamification (`/ice-breakers`).
- Backend: `IceBreakerQuestion` + `IceBreakerAnswer` models, migration, seeder (30 curated questions across fun/deep/creative/spicy).
- `IceBreakerController` with `getQuestions`, `submitAnswer`, `getAnswers` endpoints.
- 3D flip-card frontend page with category-colored borders, progress dots, and mutual reveal mechanic.
- React Query hooks: `useIceBreakerQuestions`, `useSubmitIceBreakerAnswer`, `useIceBreakerAnswers`.
- Restored corrupted `AppHeader.tsx` with Conference Pulse nav link.

## [1.0.1] - 2026-02-28
### Added
- **Conference Pulse Mode**: Professional networking overlay using the proximity engine (`/conference-pulse`).
- Backend `conferencePulse()` endpoint at `GET /api/proximity-chatrooms/conference-pulse` with skill-based filtering.
- Dark-themed frontend page with avatar-only professional cards, skill badges, and "Coffee Chat" CTAs.
- `useConferencePulse` React Query hook for real-time conference member discovery.
- Added "Conference" nav link to `AppHeader.tsx`.

## [1.0.0-RC1] - 2026-02-28
### 🎉 Release Candidate 1
- **All Phases Complete (1–7)**: Core Foundation, Enhanced UX, Social Dynamics, Advanced Features, Community & AI, Mobility & Infrastructure, and Federation.
- **257 backend tests passing** (900 assertions) across the full Laravel test suite.
- **Frontend build clean**: Next.js 16.1.1 production build compiles 94 static pages with zero errors.
- **Neighborhood Social Forum** (v0.3.52): Threaded comments and Reddit-style voting on Local Pulse artifacts.
- **Proactive AI Wingman Nudges** (v0.3.51): Real-time dating coaching via WebSocket push events.
- **Value-Aligned Matching** (v0.3.50): Deep 12-point AI Compatibility Audit with glassmorphic UI.
- **Multi-Region Edge Caching** (v0.3.49): `EdgeCacheResponse` middleware for CDN-ready public endpoints.
- **Test stability**: Fixed `REDIS_CLIENT` configuration in `phpunit.xml` to enforce `predis` in test environments.

## [0.3.52] - 2026-02-28
### Added
- Expanded **Local Pulse** into a hyper-local neighborhood forum (Phase 5).
- Added `ProximityArtifactComment` and `ProximityArtifactVote` Eloquent models to allow threaded conversations and Reddit-style scoring on Pulse elements.
- Implemented `ProximityArtifactInteractionController` to handle REST interactions (`POST /api/proximity/artifacts/{id}/comment` and `POST /api/proximity/artifacts/{id}/vote`).
- Enhanced `LocalPulse.tsx` front-end by integrating interactive upvote/downvote buttons and inline comment threads powered by React Query mutations.

## [0.3.51] - 2026-02-27
### Added
- Implemented **Proactive AI Wingman Nudges** (Phase 5).
- Created `AnalyzeConversationNudge` background job to asynchronously analyze conversation flows using LLMs every 5 messages.
- Dispatched `ConversationNudged` WebSocket events via Pusher/Reverb down private channels.
- Added sliding Framer Motion-powered "Wingman Banner" to `RealTimeChat.tsx` to display real-time dating coaching dynamically.

## [0.3.50] - 2026-02-27
### Added
- Implemented **Value-Aligned Matching & AI Compatibility Audit** (Phase 5).
- Upgraded `AiWingmanService` to build deep 12-point personality matrices matching `User` models against one another.
- Created premium JSON-LD gated endpoint `POST /api/wingman/compatibility-audit/{id}`.
- Added animated glassmorphic Next.js interface at `/compatibility-audit/[id]` with visual feedback blocks for Alignment Areas, Friction Points, and Growth Potential.

## [0.3.49] - 2026-02-27
### Added
- Implemented **Multi-Region & Edge Caching Architecture** (Phase 6).
- Created Laravel `EdgeCacheResponse` middleware (`edge.cache:MaxAge,SMaxAge`) designed to inject `Cache-Control` header declarations for public endpoints.
- Applied the edge caching middleware structurally to non-authenticated `routes/web.php` (ActivityPub Discovery) and `routes/api.php` (Platform Metrics + Server Health Status API checks).
- Expanded `DEPLOY.md` with explicit Cloudflare and Vercel CDN bypass rules blocking `edge.cache` caching collision during authenticated routes.

## [0.3.48] - 2026-02-27
### Added
- Implemented **React Native Mobile App Core** (Phase 6), wrapping the PWA into a native Expo container.
- Configured Native `app.json` explicitly requesting `NSLocationWhenInUseUsageDescription` (iOS) and `ACCESS_FINE_LOCATION` (Android) to stabilize Web Geolocation APIs inside the nested WebView.
- Added `BackHandler` logic inside `App.js` preventing the physical Android Back button from terminating the App, instead forcing backwards history routing inside the WebView itself.
- Added `onNavigationStateChange` interceptors preventing the WebView from hitting third-party URLs (e.g., Stripe, outbound social links), instead passing them directly to the native `Linking.openURL` system browser.

## [0.3.47] - 2026-02-27
### Added
- Implemented **Federated Servers (ActivityPub)** (Phase 7), turning local instances into full nodes within the broader Fediverse.
- Added `WebFingerController` and `NodeInfoController` at `/.well-known` to resolve `acct:user@domain.com` into Actor URIs and broadcast instance capabilities.
- Scaffolded `ActivityPubInboxController` and `ActivityPubOutboxController` handling structured JSON-LD payloads.
- Added Fediverse Integration toggle to the Next.js `SecuritySettingsPage`, binding to a newly migrated `is_federated` database mapping.

## [0.3.46] - 2026-02-27
### Added
- Implemented **Rust Geo-Screener** (Phase 6), a high-performance spatial indexing microservice utilizing Uber's `h3o` library via `actix-web`.
- Decoupled SQL Haversine queries from `LocalPulse`, offloading dense proximity matches to O(1) grid traversal using the local Rust `8080` background proxy.
- Set up automatic spatial index synchronization through the `SyncLocationToGeoScreener` dispatched Laravel job.

## [0.3.45] - 2026-02-27
### Added
- Implemented **ZK-Proximity Proofs** (Facade), establishing a mathematically private location verification system without leaking raw user coordinates.
- Scaffolded the `ZkProximityProof` Eloquent tracking schema and `ZKProximityService` inside the backend.
- Created the interactive `/proximity/ZKProver.tsx` frontend SNARK simulation modal and injected it into `candidate` local matching cards inside the Local Pulse dashboard.

## [0.3.44] - 2026-02-27
### Added
- Implemented **Evolving AI Avatars**, applying real-time dynamic CSS animations and visual overlays to match users' active emotional states.
- Extended the `user_profiles` schema and `Conversation` payloads to natively persist a `current_emotion` property.
- Replaced standard image objects with `EvolvingAvatar.tsx` wrappers across Profile views and RealTimeChat headers.

## [0.3.43] - 2026-02-27
### Added
- Implemented **Voice/Audio Dating Rooms**, providing drop-in "Clubhouse-style" live conversation stages.
- Created `AudioRoom` schemas, `AudioRoomController` management, and Laravel Echo WebRTC signaling routing.
- Built Next.js frontend pages `app/(protected)/audio-rooms` utilizing a custom `useWebRTC` hook for RTCPeerConnections.

## [0.3.42] - 2026-02-27
### Added
- Implemented **Burner Communication Bridge**, allowing users to generate localized 24-hour QR codes creating anonymous, self-destructing (`expires_at`) chatrooms instantly bridging physical encounters into digital safety rings.
- Added `BurnerLinkController.php`, `BurnerLink.php` schema, and `app/burner` React navigation flows.

## [0.3.41] - 2026-02-27
### Changed
- Configured dynamic `content_moderation` and `image_moderation` service container injections to un-mock `PrivacySecurityService.php` targeting native AWS Rekognition & Google Vision.
- Verified `AIMatchingService.php` correctly leverages OpenAI `text-embedding-3-small` vectors generated asynchronously upon `UserProfile` persistence.

## [0.3.40] - 2026-02-27
### Changed
- Verified the Admin Analytics Dashboard frontend (`app/analytics/page.tsx`) correctly fetches live Postgres/Redis metrics from `@/lib/api/admin.ts`.
- Overhauled the unified Help Center (`app/help/page.tsx`).
- Added 10 extensive markdown guides detailing the Tiered Privacy System (Blur to Clear), the AI Wingman tool suite (Cosmic Matches, Red/Green Flags), and the Local Private Vault encryption mechanisms.

## [0.3.39] - 2026-02-27
### Changed
- Ran `php artisan l5-swagger:generate` to output latest `api-docs.json`.
- Verified and wired frontend Settings Page toggles (Ghost Mode/Location Fuzzing) to correctly `PATCH /api/profile`.
- Validated `ConversationCoach.tsx` integration within `RealTimeChat.tsx` via `/wingman/message-feedback`.

## [0.3.38] - 2026-02-27

### Added
- **Global Documentation Rewrite**: Completely rewrote `VISION.md`, outlining the transition to Rust microservices and ZK-Proximity proofs.
- **Unified Deployment Guide**: Merged disparate deployment files into a single `DEPLOY.md` covering Docker, Kubernetes, Vercel, and DreamHost.
- **Monorepo Architecture Map**: Added `SUBMODULE_VERSIONS.md` dashboard clarifying the pure monorepo structure.
- **Notification Bell UX**: Added missing `Gift` and `Calendar` icons to `NotificationBell.tsx` and wired correct deep-links for `event` and `gift` notifications.

## [0.3.37] - 2026-02-26

### Added
- **Comprehensive Documentation Overhaul**: Rewrote `AGENTS.md`, `CLAUDE.md`, `GEMINI.md`, `GPT.md`, `copilot-instructions.md` with model-specific roles, detailed versioning protocol, code standards, and git workflow.
- **New Files**: Created `TODO.md` (short-term task list with priorities), `MEMORY.md` (persistent codebase observations and user preferences), `IDEAS.md` (expanded creative improvement proposals).
- **Version Sync**: Fixed hardcoded `v0.3.2` in `app/layout.tsx` to display the correct version.
- **HANDOFF.md**: Updated with comprehensive session analysis for model handoff continuity.

### Fixed
- **Next.js Suspense Bailout**: Wrapped `<AnalyticsProvider>` in `<Suspense>` boundary in `app/layout.tsx` to fix CSR bailout during static page generation.
- **Version Desync**: Synchronized version numbers across all model instruction files, `VERSION`, and `layout.tsx`.

## [0.3.36] - 2026-02-26

### Added
- **Tier Unlock Guide UI**: New animated `TierUnlockGuide.tsx` component in `components/chat/` displaying relationship tier progress.
  - Uses `useRelationshipTier` hook for real-time metrics (messages exchanged, days connected).
  - `framer-motion` progress bars with gradient fills showing progress to next tier.
  - Collapsible overlay integrated directly below the chat header in `RealTimeChat.tsx`.
  - Shows unlock previews (e.g., "Next: Video Chat, Full Photo Gallery").

## [0.3.35] - 2026-02-20

### Added
- **Phase 5D Parity Fulfillment**:
  - **Privacy Controls**: Added "Ghost Mode" & "Location Fuzzing" preference toggles in user settings connected to `profile.preferences`.
  - **Moderation Tools UI**: Admin settings dropdown switcher added to override `services.content_moderation.driver` configurations (`aws`, `google`, `mock`).
  - **Merchant Analytics UI**: Replaced API endpoint mock data with real UI, including tracking views, click-through rates, and `kFactor` calculations.

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
