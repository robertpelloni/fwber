# Changelog

All notable changes to the FWBer Backend API will be documented in this file.

## [Unreleased] - 2025-01-21

### Added
- **API Documentation**:
    - Added `RecommendationController` endpoints (AI Recommendations).
    - Added `WebSocketController` endpoints (Real-time communication).
    - Added `ContentGenerationController` endpoints (AI Content).
    - Added `RateLimitController` endpoints (Security).
    - Added `PhotoController` endpoints (Media management).
    - Added `ProximityArtifactController` endpoints (Location features).
- **Documentation Index**: Created `docs/README.md` as the central entry point for API docs.
- **Project Structure**: Consolidated legacy files into `archive/` directory.

### Changed
- **OpenAPI Spec**: Regenerated `storage/api-docs/api-docs.json` to include all new controllers and schemas.
- **Configuration**: Verified feature flags in `config/features.php`.

### Fixed
- **Swagger Generation**: Fixed path scanning issues by ensuring all controllers are registered in `config/l5-swagger.php`.
