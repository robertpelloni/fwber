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
- **Status**: Implemented in `components/SexQuote.tsx`.
- **Display Targets**: Homepage hero tagline, Pulse/Proximity screens (already mounted), future dashboard surfaces.
- **Next Actions**: Externalize quotes to CMS/JSON for easier moderation, add analytics event for quote impressions.

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
**Status**: Researching libraries.

## 3. "Face Reveal" Game & Auto-Reply
**Goal**: Gamify photo sharing.
- Users are encouraged to reply to a photo with a "similar" photo.
- "Auto-reply" bot/system to stimulate activity? (User phrasing: "auto reply to picture with equal sort of picture").
- "Trade face mutually" feature later.
**Tech Stack**:
- Image classification (client-side or server-side?) to determine "kind of picture".
- Logic for unlocking/trading.
- **Implementation Notes**:
  - Use on-device heuristics (blur hash, color histogram, clothing detection) to categorize "kind of picture".
  - Auto-reply bot pulls from anonymized stock selfies (faces blurred) that match the detected category.
  - Reward loop: streak counter, badge, and push/email nudges when the user fails to reciprocate.
  - Future mutual face trade: double-consent workflow + encrypted blob swap (requires Section 4 infra).
- **Metrics**: replies per photo, number of blurred uploads, conversion to mutual trade.

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

## Immediate Next Steps
1. Implement `SexQuote` component.
2. Prototype Client-Side Face Blurring.
