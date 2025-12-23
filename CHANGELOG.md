# Changelog

All notable changes to this project will be documented in this file.

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
