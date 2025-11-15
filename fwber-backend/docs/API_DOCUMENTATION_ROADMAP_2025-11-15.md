# FWBer API Documentation – Analysis & Roadmap (2025-11-15)

This document captures the current state of FWBer's OpenAPI documentation, key decisions, conventions, and a prioritized roadmap so future contributors (human or AI) can continue seamlessly.

## Overview

- Backend: Laravel 11.x, PHP 8.x
- API Docs: L5-Swagger + swagger-php (OpenAPI 3.0)
- Swagger UI: `/docs`
- Generated JSON: `storage/api-docs/api-docs.json`
- Auth: HTTP Bearer (JWT) via `bearerAuth` security scheme

## Key Decisions (Important!)

1. Centralized component schemas live in `app/Http/Controllers/Schemas.php` inside the main file-level docblock.
   - Reason: swagger-php processes annotations top-to-bottom. Defining schemas later or as separate standalone blocks may cause `$ref` resolution errors.
   - Example error avoided: `$ref '#/components/schemas/PaginatedChatMessages' not found`.
2. Global metadata, server entries, security scheme, and tag definitions live in `app/Http/Controllers/Controller.php` file-level docblock.
3. The annotation scan list is curated in `config/l5-swagger.php` under `annotations`.
   - New controllers with annotations must be added here (absolute paths via `base_path(...)`).
4. Prefer `$ref` to reusable schemas for DRY responses (e.g., `ChatMessage`, `PaginationMeta`, `PaginatedChatMessages`).
5. Standardize tags and keep them consistent across controllers to improve UX in Swagger UI.

## Current Tags

- Authentication
- Profile
- Dashboard
- Matches
- Messages
- Physical Profile
- Profile Views
- Groups
- Chatrooms
- Bulletin Boards
- Health
- Analytics
- Moderation
- Photos (added)
- Proximity Artifacts (added)
- Relationship Tiers (added)
- Safety (added)

## Reusable Schemas (Schemas.php)

Currently defined (consolidated in one file-level docblock):
- User
- ValidationError
- UnauthorizedError
- Reaction
- ChatMessage
- PaginationMeta
- PaginatedChatMessages
- ModerationAction
- ShadowThrottle

If you add new schemas, place them in `Schemas.php` inside the main docblock to guarantee `$ref` resolution.

## Controllers Covered in Annotations

Registered under `config/l5-swagger.php` → `annotations`:
- Controller.php (global OA metadata, tags, security)
- Schemas.php (component schemas)
- AuthController.php
- ProfileController.php
- HealthController.php
- LocationController.php
- DashboardController.php
- ProfileViewController.php
- MatchController.php
- Api/MessageController.php
- Api/GroupController.php
- Api/UserPhysicalProfileController.php
- BulletinBoardController.php
- ChatroomController.php
- ChatroomMessageController.php
- ProximityChatroomController.php
- ProximityChatroomMessageController.php
- AnalyticsController.php
- ModerationController.php
- PhotoController.php (added in this phase)
- ProximityArtifactController.php (added in this phase)
- Api/RelationshipTierController.php (added in this phase)
- Api/BlockController.php (added in this phase)
- Api/ReportController.php (added in this phase)
- Api/GroupMessageController.php (added in this phase)

## What Was Just Added (2025-11-15)

1. Photos (PhotoController)
   - GET /photos
   - POST /photos (multipart/form-data upload)
   - PUT /photos/{id}
   - DELETE /photos/{id}
   - POST /photos/reorder
2. Proximity Artifacts (ProximityArtifactController)
   - GET /proximity/feed
   - POST /proximity/artifacts
   - GET /proximity/artifacts/{id}
   - POST /proximity/artifacts/{id}/flag
   - DELETE /proximity/artifacts/{id}
   - GET /proximity/local-pulse
3. Relationship Tiers (Api/RelationshipTierController)
   - GET /matches/{matchId}/tier
   - PUT /matches/{matchId}/tier
   - GET /matches/{matchId}/tier/photos
4. Safety (Api/BlockController, Api/ReportController)
   - POST /blocks
   - DELETE /blocks/{blockedId}
   - POST /reports
   - GET /reports
   - PUT /reports/{reportId}
5. Group Messaging (Api/GroupMessageController)
   - POST /groups/{groupId}/messages (multipart/form-data)
   - GET /groups/{groupId}/messages (paginated)
   - POST /group-messages/{messageId}/read
   - GET /group-messages/unread-count

## Quick Verification (what we checked)

The following presence counts were checked via pattern search against the generated JSON (rough indicators, not authoritative):
- Photos: 3
- Proximity: 22
- Tiers: 3
- Safety (blocks/reports): 4
- Groups (/groups/...): 13
- GroupMsgs (/group-messages...): 2

Run the regeneration and checks yourself (optional):

```powershell
php artisan l5-swagger:generate
Select-String -Pattern '"\/photos' storage\api-docs\api-docs.json | Measure-Object | Select-Object -ExpandProperty Count
Select-String -Pattern '"\/proximity' storage\api-docs\api-docs.json | Measure-Object | Select-Object -ExpandProperty Count
Select-String -Pattern '"\/matches\/' storage\api-docs\api-docs.json | Measure-Object | Select-Object -ExpandProperty Count
Select-String -Pattern '"\/blocks|\"\/reports' storage\api-docs\api-docs.json | Measure-Object | Select-Object -ExpandProperty Count
Select-String -Pattern '"\/groups\/' storage\api-docs\api-docs.json | Measure-Object | Select-Object -ExpandProperty Count
Select-String -Pattern '"\/group-messages' storage\api-docs\api-docs.json | Measure-Object | Select-Object -ExpandProperty Count
```

## Conventions for Adding Annotations

- Always secure endpoints with `security={{"bearerAuth":{}}}` unless explicitly public.
- Attach the correct `@OA\Tag` to group endpoints logically (e.g., Photos, Proximity Artifacts, Relationship Tiers, Safety, Groups, etc.).
- For file uploads, use `multipart/form-data` with `format="binary"` on file properties.
- For arrays in request bodies, use `@OA\Property(type="array", @OA\Items(...))`.
- Use `$ref` to existing component schemas in responses when possible to remain DRY.
- When adding annotations to a new controller, add its path to `config/l5-swagger.php` annotations list.
- After schema changes, keep all `@OA\Schema` definitions in the `Schemas.php` main file-level docblock to ensure resolution order.

## Known Pitfalls & Fixes

- Issue: `$ref '#/components/schemas/YourSchema' not found` during generation.
  - Fix: Move `@OA\Schema` definitions into the `Schemas.php` main file-level docblock, before any referencing annotations are scanned.
- Issue: New annotated controller not picked up by the generator.
  - Fix: Add its absolute path to `config/l5-swagger.php` under `annotations` and regenerate.

## Immediate Roadmap (Prioritized)

Completed in this phase:
- Photos CRUD (Phase 4A) ✅
- ProximityArtifact endpoints (Ephemeral Layer) ✅
- RelationshipTier endpoints (Match tiers) ✅
- Block/Report endpoints (Safety) ✅
- GroupMessage endpoints ✅

Next priorities:
1. Recommendations (AI) – 6 endpoints
2. WebSocket/Mercure (RT) – 10+ endpoints (connect/disconnect/message/typing/presence/status/notifications)
3. ContentGeneration (AI) – 9 endpoints (profile, suggestions, optimizations, feedback, history)
4. RateLimit (Security) – 6 endpoints (status, all-status, reset, stats, suspicious-activity, cleanup)
5. Finalization – Regenerate, export Postman, write API index/README summary

## Step-by-Step: Adding Another Controller

1. Add `@OA` annotations in the controller methods.
2. If introducing new response shapes used across endpoints, define them in `Schemas.php` (main file-level docblock).
3. Add the controller file to `config/l5-swagger.php` → `annotations`.
4. Regenerate docs:
   ```powershell
   php artisan l5-swagger:generate
   ```
5. Verify new paths in `storage/api-docs/api-docs.json` and open `/docs`.

## References

- Global Metadata & Tags: `app/Http/Controllers/Controller.php`
- Component Schemas: `app/Http/Controllers/Schemas.php`
- Swagger Config: `config/l5-swagger.php`
- Generated JSON: `storage/api-docs/api-docs.json`

---

If you follow these conventions and the sequence above, you’ll avoid the common pitfalls (schema ordering, scan list omissions) and keep the API docs consistent and maintainable.
