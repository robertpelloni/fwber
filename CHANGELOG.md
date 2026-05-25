# Changelog

## [2.0.16] - 2026-05-23

### Added
- **Unified Activity Center**: Implemented a central ActivityPub activity aggregator endpoint in the backend and rewired the frontend Activity Center to use it.
- **Enhanced Inbox Processing**: Added support for `Like` and `Announce` (Boost) activities in the `FederationService`, including automated detection of interactions with local outbox content.
- **Activity Types**: Frontend now supports and badges `Like` and `Boost` activities alongside `Follow` and `Create`.

## [2.0.15] - 2026-05-22

### Added
- **Federation Automation**: New proximity artifacts are now automatically broadcast to the Fediverse if federation is enabled for the user.
- **Handshake Completion**: Implemented outbound signed `Accept` activities to complete the follow handshake with remote servers.
- **Inbox Processing**: Connected the `/inbox` endpoint to the `FederationService` processing logic.

## [2.0.14] - 2026-05-22

### Added
- **Auth Hardening**: Integrated centralized `AuthService` with dedicated unit tests.
- **Contact Sync**: Integrated `ContactSyncService` and OAuth routes for Google, Microsoft, and Facebook contact synchronization.
- **Integration UI**: Added "Connected Accounts" and "Synced Contacts" pages to the frontend.

### Fixed
- **Build Stabilization**: Resolved TypeScript and Webpack errors in newly integrated components; added missing `Tooltip` UI component.
- **Prisma Schema**: Synchronized `UserIntegration` and `SyncedContact` models into the master schema.

## [2.0.13] - 2026-05-21

### Added
- **Performance Analytics**: Hooked up the '/api/analytics/slow-requests' routes to 'fwber-backend-ts' to enable live APM signals and performance monitoring insights.

[... Legacy entries ...]
