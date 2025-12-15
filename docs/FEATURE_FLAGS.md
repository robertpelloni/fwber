# Feature Flags

This document outlines the feature flags used in the FWBer application to gate functionality.
Flags are managed via environment variables in the backend (`.env`) and frontend (`.env.local`).

## Backend Flags (`fwber-backend/config/features.php`)

These flags control API endpoints and backend logic.

| Flag | Environment Variable | Default | Description |
|------|---------------------|---------|-------------|
| `groups` | `FEATURE_GROUPS` | `true` | Enables Group creation, joining, and management. |
| `photos` | `FEATURE_PHOTOS` | `true` | Enables Photo uploads and galleries. |
| `proximity_artifacts` | `FEATURE_PROXIMITY_ARTIFACTS` | `true` | Enables the "Local Pulse" ephemeral feed. |
| `chatrooms` | `FEATURE_CHATROOMS` | `false` | Enables global/topic-based chatrooms. |
| `proximity_chatrooms` | `FEATURE_PROXIMITY_CHATROOMS` | `false` | Enables location-based chatrooms. |
| `recommendations` | `FEATURE_RECOMMENDATIONS` | `false` | Enables the AI recommendation engine. |
| `websocket` | `FEATURE_WEBSOCKET` | `false` | Enables real-time features (presence, typing, instant messaging). |
| `content_generation` | `FEATURE_CONTENT_GENERATION` | `false` | Enables AI bio/post generation. |
| `rate_limits` | `FEATURE_RATE_LIMITS` | `false` | Enables advanced rate limiting logic. |
| `analytics` | `FEATURE_ANALYTICS` | `false` | Enables internal analytics tracking. |
| `face_reveal` | `FEATURE_FACE_REVEAL` | `false` | Enables the progressive face reveal system. |
| `local_media_vault` | `FEATURE_LOCAL_MEDIA_VAULT` | `false` | Enables the client-side encrypted media vault. |
| `moderation` | `FEATURE_MODERATION` | `false` | Enables automated moderation tools. |
| `media_analysis` | `FEATURE_MEDIA_ANALYSIS` | `false` | Enables AWS Rekognition integration for media safety. |
| `ai_wingman` | `FEATURE_AI_WINGMAN` | `false` | Enables AI Roast, Hype, and conversation suggestions. |
| `video_chat` | `FEATURE_VIDEO_CHAT` | `false` | Enables WebRTC video calling. |

## Frontend Flags (`fwber-frontend/.env.local`)

These flags control UI elements and client-side logic.

| Flag | Environment Variable | Description |
|------|---------------------|-------------|
| `client_face_blur` | `NEXT_PUBLIC_FEATURE_CLIENT_FACE_BLUR` | Enables client-side face blurring on upload. |
| `face_reveal` | `NEXT_PUBLIC_FEATURE_FACE_REVEAL` | Shows the Face Reveal UI components. |
| `local_media_vault` | `NEXT_PUBLIC_FEATURE_LOCAL_MEDIA_VAULT` | Shows the Media Vault in settings. |

## How to Enable a Feature

1.  **Backend**: Add the variable to `fwber-backend/.env` (e.g., `FEATURE_AI_WINGMAN=true`).
2.  **Frontend**: Add the variable to `fwber-frontend/.env.local` (e.g., `NEXT_PUBLIC_FEATURE_FACE_REVEAL=true`).
3.  **Restart**: Restart the backend (Laravel) and frontend (Next.js) servers to apply changes.

## Runtime Toggling

Admin users can toggle features at runtime via the `/admin/settings` page. These changes are persisted in the database/cache and override the environment variables temporarily.
