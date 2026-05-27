# Changelog

## [2.0.29] - 2026-05-24

### Changed
- **Repository Synchronization**: Executed an intensive repository refresh, synchronized submodules, and reconciled all active feature branches into `main`.
- **Submodule Sanitization**: Updated all recursive submodules to latest tracking commits and verified workspace hygiene.

## [2.0.28] - 2026-05-24

### Added
- **ActivityPub Notifications**: Implemented real-time in-app notifications and socket broadcasts for Follow, Like, and Boost events.
- **Real-Time Notification UI**: Updated the frontend to handle ActivityPub interactions with themed toasts and direct CTAs to the Activity Center.

## [2.0.27] - 2026-05-24

### Added
- **Autonomous Self-Correction**: Implemented `MaintenanceService` to monitor `autonomous_actions` failure rates and automatically toggle `strict_mode` based on system health.
- **Solana Loyalty Bridge**: Integrated `SolanaBridgeService` into venue check-ins to trigger on-chain NFT minting signals based on merchant-defined thresholds.
- **Instrumentation Expansion**: Full visibility for `AuthService` (Login/Register) and `ContactSyncService` tasks in the autonomous ledger.
- **Merchant Loyalty UI**: Launched a unified program configuration and signal monitoring dashboard at `/merchant/loyalty`.

### Fixed
- **Testing Isolation**: Refactored Merchant Loyalty API tests to use isolated database contexts, eliminating interference during parallel execution.
- **ESM Compatibility**: Resolved Prisma named export issues in Jest ESM environment via explicit mock definitions.

## [2.0.24] - 2026-05-24

### Added
- **Service Instrumentation**: Integrated `GeoSpoofDetectionService` and `GeoScreenerService` into the autonomous execution protocol for real-time monitoring of security and proximity health.
- **Activity Center UI**: Finalized the frontend Activity Center (`/settings/federation/activity`) to display unified ActivityPub interactions (Follows, Likes, Boosts).
- **Integrated Contact Sync**: Finalized the Connected Accounts UI and Next.js API proxy for Google, Microsoft, and Facebook contact synchronization.

### Fixed
- **API Proxies**: Corrected Next.js internal API routes to map accurately to the primary TypeScript backend ports.
- **Type Safety**: Enforced strict typing in Federation route handlers to satisfy compiler null-checks.

- **Type Safety**: Enforced strict typing in Federation route handlers to satisfy compiler null-checks.

