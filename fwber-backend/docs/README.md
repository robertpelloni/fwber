# FWBer API Documentation Index

This directory contains documentation for the FWBer API. The primary source of truth is the generated OpenAPI (Swagger) documentation, which is available at `/docs` when the application is running.

## Quick Links

- **[API Reference (Swagger UI)](/docs)**: Interactive API documentation.
- **[API Roadmap](./API_DOCUMENTATION_ROADMAP_2025-11-15.md)**: Current state, key decisions, and future plans for API documentation.
- **[MVP Scope](./MVP_SCOPE.md)**: Definition of the Minimum Viable Product scope.

## Key Controllers & Features

The following controllers are fully annotated and form the core of the API:

### Authentication & User
- `AuthController`: Registration, Login, Logout.
- `ProfileController`: User profile management.
- `PhotoController`: Photo upload, management, and privacy.

### Social & Matching
- `MatchController`: AI-powered matching and compatibility scoring.
- `Api/MessageController`: Direct messaging between users.
- `Api/GroupController`: Group creation and management.
- `Api/GroupMessageController`: Group chat functionality.

### Proximity & Real-time
- `ProximityArtifactController`: Location-based ephemeral content (Local Pulse).
- `WebSocketController`: Real-time connection handling (Mercure/Pusher).

### Safety & Moderation
- `Api/BlockController`: User blocking.
- `Api/ReportController`: Content and user reporting.
- `ModerationController`: Admin moderation actions.

### AI & Advanced Features
- `RecommendationController`: AI-powered personalized recommendations.
- `ContentGenerationController`: AI content generation and optimization.
- `RateLimitController`: Advanced rate limiting and suspicious activity detection.

## Development Guide

### Generating Documentation
To regenerate the OpenAPI documentation after modifying annotations:

```bash
cd fwber-backend
php artisan l5-swagger:generate
```

### Adding New Endpoints
1.  Add `@OA` annotations to your controller methods.
2.  Ensure any new component schemas are defined in `app/Http/Controllers/Schemas.php`.
3.  Register the controller in `config/l5-swagger.php` under the `annotations` array.
4.  Run the generation command above.

### Exporting for Postman
The generated JSON file is located at `storage/api-docs/api-docs.json`. You can import this file directly into Postman to create a collection.
