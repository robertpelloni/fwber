# Changelog

All notable changes to this project will be documented in this file.

## [0.3.17] - 2025-12-30

### Fixed
- **Testing**: Added `cypress/e2e/match-insights.cy.js` to verify the AI Match Insights unlock flow.
- **Testing**: Fixed selectors in `matching-flow.cy.js` to correctly target the "Like" button.
- **Development**: Improved `lib/echo.ts` to handle WebSocket fallback configuration in test/dev environments, reducing console noise.
- **Frontend**: Fixed `ProfileViewModal` to correctly update state after unlocking AI Insights, ensuring the UI refreshes without a page reload.

## [0.3.3] - 2025-12-28

### Added
- **Viral Growth**: "Roast Your Date" feature.
    - **Frontend**: Public page (`app/roast-date/page.tsx`) to generate shareable AI roasts.
    - **Sharing**: Integrated `navigator.share` for mobile and clipboard copy for desktop.
- **Viral Growth**: "Who Likes You" Unlock.
    - **Frontend**: Updated Premium page to show blurred admirer cards.
    - **Mechanism**: Implemented "Share to Unlock" allowing users to reveal admirers by sharing the app.
    - **Backend**: `PremiumController` now supports sanitizing admirer data for non-premium users.

## [0.3.16] - 2025-12-28

### Added
- **Dashboard**: Created `docs/dashboard/PROJECT_STRUCTURE_DASHBOARD.md` to provide a clear overview of the project directory structure, submodule status, and component versions.

### Changed
- **Submodules**: Updated all submodules (if any) to their latest upstream versions and synchronized the project state.
- **Maintenance**: Performed a general sync and update of the codebase, ensuring all feature branches are merged and up-to-date.

## [0.3.15] - 2025-12-28

### Fixed
- **Critical**: Resolved "Module factory not available" crash on production by removing the `components/realtime/index.ts` barrel file and updating 10 dependent files to use direct imports. This eliminates the circular dependency cycle causing the Webpack/Turbopack build failure.

### Changed
- **Protocol**: Established a formal "Operational Charter & Protocol" in `docs/LLM_INSTRUCTIONS.md` to standardize agent behavior, versioning, and documentation.
- **Documentation**: Updated `AGENTS.md`, `CLAUDE.md`, and `copilot-instructions.md` to reference the new Master Protocol.
- **Dashboard**: Updated `docs/PROJECT_STRUCTURE.md` to serve as the central dashboard for project layout and version tracking.
- **Versioning**: Bumped version to 0.3.15 to mark the protocol enforcement and critical fix.

## [0.3.2] - 2025-12-27


### Fixed
- **Critical**: Resolved "Module factory not available" crash on production by refactoring `PhotoUpload` to break circular dependencies with `PhotoGallery` and using dynamic imports.

### Changed
- **Maintenance**: Comprehensive update of all project dependencies.
    - **Backend**: Upgraded Laravel Framework (v12.44.0), Sentry, and Stripe SDKs.
    - **Frontend**: Updated 77 NPM packages to latest stable versions.
- **Documentation**: Added `docs/PROJECT_STRUCTURE.md` detailing the monorepo layout and module status.

## [0.3.14] - 2025-12-27

### Added
- **Event Types**: Implemented support for categorizing events.
    - **Backend**: Added `type` column to `events` table (Standard, Speed Dating, Party, Meetup, Workshop).
    - **Backend**: Updated `EventController` to support filtering by `type`.
    - **Frontend**: Updated `CreateEventPage` to allow selecting event type.
    - **Frontend**: Updated `EventsPage` to filter events by type.

## [0.3.14] - 2025-12-27

### Fixed
- **Critical**: Resolved "Module factory not available" crash on production by removing circular dependency between `lib/faceBlur.ts` and `lib/featureFlags.ts`.
- **Refactor**: Decoupled `lib/faceBlur.ts` from feature flags, moving the check to the consumer components (`PhotoUpload.tsx`, `register/page.tsx`).

## [0.3.13] - 2025-12-27

### Added
- **Video Chat**: Implemented Real-time Face Blur for privacy.
    - **Frontend**: Created `useVideoFaceBlur` hook using `face-api.js` and Canvas API.
    - **UI**: Added "Ghost" toggle button to Video Call controls.
    - **Performance**: Optimized face detection loop to run every 200ms while rendering at 30fps.

## [0.3.12] - 2025-12-27

### Changed
- **Documentation**: Centralized all LLM instructions into `docs/LLM_INSTRUCTIONS.md`.
- **Documentation**: Created `docs/PROJECT_STRUCTURE.md` to document the monorepo layout.
- **Documentation**: Updated `AGENTS.md`, `CLAUDE.md`, `GEMINI.md`, `GPT.md` to reference the master protocol.
- **Process**: Formalized the versioning and handoff protocols.

## [0.3.11] - 2025-12-27

### Changed
- **Real-Time**: Migrated Bulletin Boards from Mercure to Pusher (Laravel Echo).
    - **Frontend**: Refactored `use-bulletin-boards.ts` to use `usePusherLogic`.
    - **Frontend**: Shimmed `use-mercure-sse.ts` to redirect legacy calls to Pusher.
    - **Frontend**: Removed Mercure rewrite rules from `next.config.js`.
    - **Backend**: Created `BulletinMessageCreated` and `BulletinBoardActivity` events.
    - **Backend**: Updated `BulletinBoardController` to use standard Laravel Broadcasting.
    - **Config**: Updated `broadcasting.php` to respect `BROADCAST_DRIVER` env var.

## [0.3.10] - 2025-12-26

### Fixed
- **Photo Upload**: Fixed "Select Photos" button not working by removing conflicting `ref` on the file input in `PhotoUpload` component.
- **Mercure**: Reverted frontend production configuration to use `demo.mercure.rocks` as requested.

## [0.3.9] - 2025-12-26

### Fixed
- **Mercure**: Fixed Docker networking issue where backend could not publish updates due to incorrect `MERCURE_INTERNAL_URL`. Updated `.env` to use container hostname `fwber-mercure`.
- **Photos**: Fixed photo upload failure in local development caused by OpenAI quota limits. Disabled `FEATURE_MEDIA_ANALYSIS` in `.env` to bypass the check.

## [0.3.8] - 2025-12-25

### Added
- **Testing**: Added `cypress/e2e/token-gated-chatrooms.cy.js` to verify creation and join flows for paid chatrooms.
- **UI**: Added "Premium" badge (💎) to chatroom cards in the list view to indicate entry fees.

## [0.3.7] - 2025-12-25

### Added
- **Chatrooms**: Implemented Token-Gated Chatrooms.
    - **Backend**: Updated `ChatroomController` to support `token_entry_fee` in `store` and `join` methods.
    - **Frontend**: Updated `CreateChatroom` form to allow setting an entry fee.
    - **Frontend**: Updated `ChatroomPage` to show a "Pay & Join" overlay for non-members in preview mode.
    - **API**: Updated `Chatroom` interface and response structures.

## [0.3.6] - 2025-12-25

### Added
- **Crypto**: Merged `feature/solana-crypto-integration` into `main`, adding `auth/login-wallet` endpoint and resolving conflicts in `api.php`.

### Changed
- **Documentation**: Updated `ROADMAP.md` to reflect current status (Post-Launch Monitoring) and completed milestones (Mercure Stability, Solana Integration).

## [0.3.5] - 2025-12-25

### Fixed
- **Mercure**: Fixed 503 Service Unavailable and 401 Unauthorized errors by updating the JWT key to the correct value required by the Mercure Demo Hub (`!ChangeThisMercureHubJWTSecretKey!`).
- **Mercure**: Reverted temporary custom JWT encoding hack in `MercurePublisher.php` to use the standard and secure `firebase/php-jwt` library now that the key length issue is resolved.
- **Configuration**: Updated `.env`, `.env.example`, and `.env.testing` with the correct Mercure credentials.

## [0.3.4] - 2025-12-24

### Fixed
- **Photo Upload**: Forced file input to `display: block` (with `sr-only` class) to ensure it remains interactive and accessible, fixing issues where `display: none` prevented the file dialog from opening.

## [0.3.3] - 2025-12-24

### Fixed
- **Mercure**: Fixed 401 Unauthorized and CORS errors on production by updating `Caddyfile` to include `fwber.vercel.app` and hardcoding correct JWT keys.
- **Photo Upload**: Fixed file selection dialog not opening on some devices by replacing `display: none` with `sr-only` class for the file input.
- **Photo Upload**: Added a visible "Select Photos" button as a fallback for drag-and-drop.

## [0.3.2] - 2025-12-24

### Fixed
- **Mercure**: Fixed `MercureAuthController` to correctly handle secure cookies in mixed environments (HTTP localhost vs HTTPS production), resolving 401/CORS errors during local development.
- **Photos**: Changed default `avatar_mode` in `PhotoController` from `generated-only` to `upload`, fixing the 403 Forbidden error that prevented users from uploading photos during onboarding.

## [0.3.1] - 2025-12-24

### Fixed
- **Backend Stability**: Implemented robust defensive coding in `PhotoController` and `ProfileController` to prevent 500 Server Errors when database schema is out of sync (missing columns/tables).
- **Mercure**: Fixed CORS issues by whitelisting Vercel preview domains in `start_mercure_shared.sh`.
- **Mercure**: Added JWT token to `MercureAuthController` response body to allow manual token handling when cross-domain cookies fail.
- **Database**: Added `schema:fix-missing` artisan command for manual database repair in production environments where migrations fail to run.
- **Middleware**: Updated `CheckDailyBonus` and `TrackUserActivity` to catch `Throwable` instead of `Exception`, preventing fatal errors (like missing classes or services) from crashing the entire API.
- **Controllers**: Updated `PhotoController` and `MercureAuthController` to catch `Throwable` to prevent 500 errors during file uploads or auth checks.

## [0.3.0] - 2025-12-22

### Added
- **Solana Integration**: Merged `feature/solana-crypto-integration` into `main`.
    - Added `SolanaProvider` for wallet connection.
    - Added `TipButton` for crypto tipping.
    - Updated `events` table to support token costs.

### Fixed
- **Frontend Build**: Added missing `@heroicons/react` dependency to `fwber-frontend/package.json`.
- **Merge Conflicts**: Resolved conflicts in `FriendList.tsx`, `RealTimeChat.tsx`, `layout.tsx`, `profile/[id]/page.tsx`, `GroupService.php`, and migrations.

## [0.2.9] - 2025-12-22

### Fixed
- **Frontend Build**: Resolved "Module not found" errors for `react-hook-form` and `@hookform/resolvers` by performing a clean install with `--ignore-scripts` to bypass corrupted binaries.
- **Frontend Build**: Fixed TypeScript error in `FriendList.tsx` where `TipButton` was receiving an invalid `variant` prop. Updated to use `compact={true}`.
- **Frontend**: Fixed `useMemo` dependency warning in `SolanaProvider.tsx`.

## [0.2.8] - 2025-12-22

### Changed
- **Landing Page**: Complete redesign of the landing page (`page.tsx`) to remove "bro" tone and emphasize privacy and professionalism.
- **Theming**: Implemented a multi-theme engine supporting "Classic", "Speakeasy", "Neon", and "Clean" styles alongside Light/Dark mode.
- **UI**: Replaced simple Theme Toggle with a Dropdown Menu for selecting both Mode and Style.
- **Navigation**: Implemented "Mobile-First" bottom navigation bar for better usability on small screens.
- **Content**: Updated Hero section copy to "Real Connections. Zero Trace." and curated quotes to be more romantic/intimate.

## [0.2.7] - 2025-12-18

### Fixed
- **Deployment**: Fixed `npm ci` failure on deployment environments by resolving `viem` peer dependency conflicts.
- **Dependencies**: Added `viem` (2.23.11) to `fwber-frontend/package.json` to satisfy `@reown/appkit` requirements.
- **Dependencies**: Upgraded `typescript` to latest (5.9.3) in `fwber-frontend` to satisfy `ox` peer dependency.
- **Frontend**: Fixed `useMemo` dependency warning in `SolanaProvider`.
- **Frontend**: Fixed TypeScript build error in `lib/e2e/crypto.ts` regarding `BufferSource` type mismatch.
- **Frontend**: Fixed TypeScript build error in `lib/vault/crypto.ts` regarding `BufferSource` type mismatch.
- **Frontend**: Added `pino-pretty` to `devDependencies` to resolve build warning.
- **Frontend**: Updated `package-lock.json` to include `pino-pretty` and ensure sync with `package.json`.
- **Frontend**: Fixed another `BufferSource` type mismatch in `lib/vault/crypto.ts` (`createPassphraseVerifier`).

## [0.2.6] - 2025-12-17

### Fixed
- **Onboarding**: Fixed "Error updating profile" issue where empty location fields (city, state) or zero coordinates caused validation failures. Implemented client-side sanitization to remove empty fields before submission.
- **Testing**: Added `cypress/e2e/onboarding-flow.cy.js` to verify profile update payload sanitization.

## [0.2.5] - 2025-12-17

### Fixed
- **Login**: Fixed an issue where the login screen would freeze or hang indefinitely if the backend was unreachable or the request timed out. Added safeguards and timeouts to `AuthContext` and `LoginPage`.

## [0.2.4] - 2025-12-15

### Added
- **Performance**: Added database indexes for `token_balance`, `current_streak`, `last_active_at`, and `match_assists(status, matchmaker_id)` to optimize Leaderboard and Gamification queries.

### Verified
- **Viral Growth**: Confirmed full implementation of Achievements, Wingman, and Token systems across Backend and Frontend.
- **Monitoring**: Confirmed Sentry configuration is present for both Backend and Frontend.

## [0.2.3] - 2025-12-15

### Fixed
- **Infrastructure**: Resolved "Double CORS" header issues by removing hardcoded headers from `.htaccess` and Nginx, allowing Laravel to handle CORS exclusively.
- **Mercure**: Created `mercure_proxy_htaccess` to correctly proxy real-time traffic on shared hosting while allowing Let's Encrypt SSL validation.
- **Frontend**: Disabled `automaticVercelMonitors` in `next.config.js` to prevent 404 errors on non-Vercel hosting.

## [0.2.2] - 2025-12-15

### Added
- **E2E Testing**:
    - Added `gifts.cy.js` to test virtual gifting flow.
    - Added `report-user.cy.js` to test user reporting flow.
    - Enhanced `video-chat.cy.js` to test incoming call simulation.

### Fixed
- **Deployment**: Hardened `deploy.sh` with `trap` to ensure site exits maintenance mode even if deployment fails.
- **Mercure**: Removed conflicting CORS configuration in `start_mercure_shared.sh` to resolve startup issues.
- **Documentation**: Fixed syntax error in `WebSocketController` to enable API documentation generation.

## [0.2.1] - 2025-12-15

### Fixed
- **Profile Update**: Fixed validation errors preventing users from updating profiles with empty optional fields.
- **Profile Update**: Fixed 500 Internal Server Error caused by misconfigured Slack logging in production.
- **Mercure**: Fixed CORS errors for real-time service by adding explicit headers in Nginx configuration.

## [0.2.0] - 2025-12-15

### Added
- **Viral Quest**: Implemented "Share to Unlock" mechanic for Viral Content.
    - Users can now unlock 24h of Gold Premium by getting 5 unique views on their shared content (Roast, Vibe Check, etc.).
    - Added progress tracking banner on the shared content page for the owner.
- **Backend**:
    - Added `views` and `reward_claimed` columns to `viral_contents` table.
    - Updated `ViralContentController` to track unique views and automatically award Gold Premium.

## [0.1.0] - 2025-12-15

### Added
- **RealTimeChat Integration**:
    - Video Calling with WebRTC and Mercure signaling.
    - Safety Tools (Report/Block) directly in chat.
    - Profile Viewing from chat header.
    - Gift Shop integration.
    - Match Insights and Date Planner.
    - End-to-End Encryption for messages.
    - Message Translation.
    - Enhanced file upload support (Images, Video, Audio).
- **Viral Growth Features**:
    - Wingman Bounties and Referral System.
    - "Share to Unlock" photos.
    - AI Profile Roast & Hype.
    - Daily Streaks.
    - AI Vibe Check.
- **Advanced AI**:
    - AI Conversation Coach.
    - Automated Audio Moderation.
    - Smart Voice Replies.
    - Generative AI Match Explanations.
- **Privacy & Security**:
    - Incognito Mode.
    - Account Deletion and Data Export.
    - Two-Factor Authentication (2FA).
- **User Experience**:
    - User Onboarding Wizard.
    - Travel Mode (Passport).
    - Blockchain-powered Token System.

### Changed
- Refactored `RealTimeChat` component to be self-contained and feature-rich.
- Updated `MessagesPage` to use the new `RealTimeChat` component.
- Improved Landing Page branding and UI.

### Fixed
- "Maximum update depth exceeded" error in `RealTimeChat`.
- Various backend test failures and migration idempotency issues.
