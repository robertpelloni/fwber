# Changelog

All notable changes to this project will be documented in this file.

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
