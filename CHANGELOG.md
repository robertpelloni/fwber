# Changelog

All notable changes to this project will be documented in this file.

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
