# Media Messaging Feature (P2)

## Overview
Adds support for sending rich media (image, audio, video, generic file) in one-on-one matched user conversations. Builds on existing `Message` model and extends validation and storage handling for secure, size‑bounded uploads.

## Database
Columns added (idempotent):
- media_url (string, nullable)
- media_type (string, nullable) – MIME type (e.g. image/jpeg)
- media_duration (int, nullable) – audio/video duration in seconds
- thumbnail_url (string, nullable) – placeholder for video thumbnails (future)

Authoritative migration: `2025_11_10_000020_add_media_fields_to_messages_table.php`
Compatibility guard migration retained: `2025_11_10_221950_add_media_fields_to_messages_table.php` (now idempotent).

## Validation Rules
Endpoint: `POST /api/messages`
Minimum payload: either `content` OR `media` must be present.

Accepted `message_type` values: `text`, `image`, `audio`, `video`, `file`.
If `message_type` omitted and media provided, inferred from MIME prefix.

Size caps (KB):
- image: 5120 (≈5MB)
- audio: 3072 (≈3MB)
- video: 15360 (≈15MB)
- file: 2048 (≈2MB)

Duration limits:
- audio: 1–120s
- video: 1–60s
Validation error if outside bounds (no clamping).

Allowed image MIME types: `image/jpeg`, `image/png`, `image/gif`, `image/webp`.
Other media types rely on MIME prefix grouping (`audio/`, `video/`).

Error responses conform to Laravel validation structure:
```json
{
  "message": "The given data was invalid.",
  "errors": { "media": ["video exceeds maximum size of 15360 KB"] }
}
```

## Storage
Files stored on `public` disk under: `messages/{sender_id}/`.
Public URL generated via `Storage::url()`.

## Service Abstraction
`App\Services\MediaUploadService` centralizes storage and (future) thumbnail generation. Controller delegates file write & URL derivation to service.

## Controller Enhancements
`MessageController@store` now:
- Enforces existence of active match and block checks.
- Infers type when not supplied.
- Validates MIME & size & duration per media category.
- Returns standardized validation errors.
- Persists message with media metadata.

## Read Receipts & Presence
Unaffected; media messages integrate seamlessly with existing listing & read receipt flows.

## Tests
`MediaMessagingTest` covers:
- Voice (audio) message
- Image message
- Video message
- Plain text message
- Oversized media rejection
- Excess audio duration rejection
- Conversation aggregation containing mixed media

All feature tests pass (71 passing overall, 0 failing; 10 skipped AI integration tests).

## Future Enhancements
- Actual video thumbnail generation (e.g. FFmpeg integration).
- Image dimension validation (max resolution caps).
- Virus scanning / content hashing.
- Rate limiting per media type.
- S3 or CDN storage backend.
- Progressive upload & transcoding pipeline.

## Security Considerations
- MIME enforcement for images to mitigate disguised executable uploads.
- Size constraints to prevent resource exhaustion.
- Match & block checks prior to file persistence (avoid orphan data writes).

## Migration Strategy Note
Two migrations exist for backward compatibility. Only one performs work; the second safely no-ops if columns already present, preventing duplicate column errors on fresh or existing databases.

## Summary
Media messaging (P2) is now fully implemented with robust validation, storage abstraction, and comprehensive tests. The system is ready for incremental UX improvements and advanced media processing.
