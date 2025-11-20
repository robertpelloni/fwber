# Roadmap Expansion: Privacy, Gamification, and Content Features
Date: 2025-11-20

## 1. Dynamic Sex Quotes
**Goal**: Display a provocative or insightful quote about sex/relationships that changes on every page refresh.
**Implementation**:
- Create a `SexQuote` component.
- Maintain a list of quotes in a constant file.
- Randomly select one on mount (client-side) or render (server-side).
- **Content**: Include quotes like "Even the Queen of England was a slut in heat" and taglines like "Everybody wants to get laid".
- **Status**: Implemented in `components/SexQuote.tsx`.

## 2. Client-Side Face Blurring
**Goal**: Automatically detect and blur faces in user-uploaded photos before they are saved or shared.
**Requirements**:
- Client-side processing (privacy first).
- Automatic detection.
**Tech Stack**:
- `face-api.js` or `mediapipe` (TensorFlow.js).
- Canvas API for image manipulation.
**Status**: Researching libraries.

## 3. "Face Reveal" Game & Auto-Reply
**Goal**: Gamify photo sharing.
- Users are encouraged to reply to a photo with a "similar" photo.
- "Auto-reply" bot/system to stimulate activity? (User phrasing: "auto reply to picture with equal sort of picture").
- "Trade face mutually" feature later.
**Tech Stack**:
- Image classification (client-side or server-side?) to determine "kind of picture".
- Logic for unlocking/trading.

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

## Immediate Next Steps
1. Implement `SexQuote` component.
2. Prototype Client-Side Face Blurring.
