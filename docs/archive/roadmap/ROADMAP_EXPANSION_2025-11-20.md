# Roadmap Expansion: Privacy, Gamification, and Content Features
Date: 2025-11-20

## 1. Dynamic Sex Quotes
**Goal**: Display a provocative or insightful quote about sex/relationships that changes on every page refresh.
**Implementation**:
- Create a `SexQuote` component.
- Maintain a list of quotes in a constant file.
- Randomly select one on mount (client-side) or render (server-side).
- **Content**: Include quotes like "Even the Queen of England was a slut in heat" and taglines like:
  - "Everybody wants to get laid"
  - "Every body likes sex"
  - "Every body gets horny"
- **Status**: ✅ Implemented in `components/SexQuote.tsx` and rendered on hero + proximity surfaces (homepage hero + proximity feed).
- **Display Targets**: Homepage hero tagline, Pulse/Proximity screens (already mounted), future dashboard surfaces.
- **Next Actions**:
  1. Externalize quotes to CMS/JSON for easier moderation.
  2. Add an analytics event for quote impressions (`quote_impression`).
  3. Document governance + storytelling guidelines in the content strategy doc.

## 2. Client-Side Face Blurring
**Goal**: Automatically detect and blur faces in user-uploaded photos before they are saved or shared.
**Requirements**:
- Client-side processing (privacy first).
- Automatic detection.
**Tech Stack**:
- `face-api.js` or `mediapipe` (TensorFlow.js).
- Canvas API for image manipulation.
- **Implementation Plan**:
  1. Ship opt-in beta flag (`feature:client_face_blur`).
  2. Download lightweight face detection model at runtime; cache with IndexedDB.
  3. Render preview canvas showing blurred/unblurred comparison.
  4. Persist blurred bitmap locally; upload only if user explicitly shares.
- **Open Questions**: Performance on low-end devices, fallback UX when detection fails, guideline for multi-face images.
- **Status**: ✅ **Live** (Beta). `lib/faceBlur.ts` is fully implemented using `@vladmandic/face-api` with CDN-based model loading. Feature flag `NEXT_PUBLIC_FEATURE_CLIENT_FACE_BLUR` is enabled in `.env.local`. Telemetry for preview events is active.
- **Current Coverage**:
  - Metadata: `face_blur_metadata` appended client-side and consumed by `PhotoController::emitFaceBlurTelemetry()`.
  - UX: warnings surface when faces are missing or blur fails; compare toggle + slider modal shipping in `PhotoUpload`; Cypress tests still cover default (non-blur) path.
  - **Note**: Telemetry expansion to Messaging and Registration is currently blocked as those features do not yet support file uploads.
- **Next Actions (2025-11-24)**:
  1. Monitor telemetry for model load failures.
  2. Consider bundling models if CDN reliability becomes an issue.
  3. Publish beta enablement instructions (flag settings, warnings, slider usage) in docs/testing now that compare UX is live.

## 3. "Face Reveal" Game & Auto-Reply
- **Goal**: Gamify privacy. Users can "reveal" their unblurred photo to a match after X messages or mutual consent.
- **Technical**:
  - Store *original* (encrypted) + *blurred* (public) versions of photos.
  - Key management: Server holds key, but only releases it (or re-signs URL) upon "Reveal" event.
  - Auto-Reply: Simple rule-based responder for "Hi/Hello" to keep engagement while user is offline.
- **Status**: **Design Complete**. See `docs/implementation/FACE_REVEAL_EXPERIMENT.md`.
- **Next Actions**:
  1. (Done) Create migrations for `photo_reveals` table and `photos` columns.
  2. (Done) Implement `AutoReplyService` and `PhotoRevealController`.
  3. Implement frontend UI for "Reveal" action and viewing original photo.

## 4. Local-First Encrypted Media Storage
**Goal**: Maximum privacy. Media is NOT stored on the server in plain text (or at all?).
**Requirements**:
- "All media encrypted based on password".
- "Stored locally".
**Architecture Shift**:
- **Storage**: `IndexedDB` or `OPFS` (Origin Private File System).
- **Encryption**: WebCrypto API (AES-GCM) using a key derived from the user's password (PBKDF2).
- **Sync**: If "stored locally" means *only* on the device, no sync is needed. If it means "encrypted locally then stored on server", we need an encrypted blob storage.
- **Implications**: If a user loses their password/device, data is lost. This is a "Zero Knowledge" architecture.
- **Implementation Tasks**:
  1. Add `FEATURE_LOCAL_MEDIA_VAULT` flag and `.env` entry.
  2. Build vault service wrapping IndexedDB + WebCrypto, expose hooks for uploads/gallery views.
  3. Update upload flow to blur faces (Section 2) before encryption step.
  4. Provide recovery UX warning + manual export/import JSON bundle.
- **Risks**: Backup complexity, quota limits (~2GB desktop, ~50MB mobile), accessibility when user clears site data.
- **Spike Doc**: `docs/implementation/spikes/LOCAL_MEDIA_VAULT_SPIKE_2025-11-20.md` outlines architecture + constraints for the beta.

## Immediate Next Steps (Late November 2025)
1. (Done) Harden client-side face blur beta: ship model caching + preview telemetry visibility and document enablement instructions.
2. (Done) Outline Face Reveal + Auto-Reply experiment scope and feature flag requirements.
3. (Done) Implement Face Reveal backend (migrations + controller).
4. Implement Face Reveal frontend (Reveal button + secure image viewer).
5. Draft Local Media Vault spike (encryption + storage constraints) for upcoming sprint.
