# Sex Quotes & Privacy Feature Notes
Date: 2025-11-20

## Dynamic Sex Quotes / Tagline System
- Component: `fwber-frontend/components/SexQuote.tsx`
- Surfaces: Home hero (`app/page.tsx`), Proximity feed (`components/ProximityFeed.tsx`), future dashboard modules.
- Content governance: maintain curated JSON, add moderation tags (NSFW, mild, humorous) for future filtering.
- Telemetry: log `quote_impression` with quote ID to measure engagement.

## Client-Side Face Blurring
- **Implementation status:** MVP landed in `fwber-frontend` (see `components/PhotoUpload.tsx`, `lib/faceBlur.ts`, `lib/featureFlags.ts`). Additional upload surfaces now show a ShieldCheck banner when blur is active, and telemetry now captures client results for each upload.
- **Library choice:** `@vladmandic/face-api` (TinyFaceDetector) with automatic backend selection (WebGL → CPU fallback). Models currently load from `https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/`.
- **Feature flag:**
  - Frontend: `NEXT_PUBLIC_FEATURE_CLIENT_FACE_BLUR` (documented in `.env.example`). Enables blur processing + UX messaging.
  - Backend (future): `FEATURE_CLIENT_FACE_BLUR` middleware toggle once API routes enforce storage rules.
- **Current flow:** user selects image → `faceBlur.ts` ensures models + draws to canvas → detected bounding boxes expanded 15% → blurred region replaced in-place via canvas filter → resulting blob uploaded with `-blurred` suffix. Per-file badges display detected face counts.
- **Preview comparison workflow (2025-11-20):**
  - `PhotoUpload` now caches both the original object URL and a blurred derivative for every processed file.
  - A floating Blurred/Original toggle appears on each preview (only when blur actually ran) so QA can visually confirm the transformation before the upload finishes.
  - We default to the blurred view, remember the user’s choice per preview, and simultaneously clean up both object URLs whenever the preview is removed, the upload completes, or the component unmounts.
  - ⚙️ Enablement steps:
    1. Set `NEXT_PUBLIC_FEATURE_CLIENT_FACE_BLUR=true` in `fwber-frontend/.env.local` (already listed in `.env.example`).
    2. Run `npm run dev` (or the Cypress suite) so the App Router sees the flag at build time.
    3. Visit any screen using `PhotoUpload` (profile editor, onboarding) → drop/import photos → use the new toggle to compare.
    4. Watch the inline “Face blur notices” list for warnings such as `no_faces_detected`—they mirror the metadata sent to the backend.
- **Fallback handling:**
  - Any `FaceBlurError` downgrades gracefully (upload proceeds but warning pill appears in the UI and the file remains unaltered).
  - If no faces detected, we notify the user that the image may continue unblurred.
- **QA items:**
  - Confirm <500 ms processing on M1 / Ryzen 7 for 1080p inputs.
  - Verify warnings render when WebGL is unavailable (force `tf.setBackend('cpu')`).
  - Ensure Photo Management + Profile screens display the banner only when the flag is on.
- **Telemetry:** Every upload now ships metadata from the client to the backend. `PhotoController` emits `face_blur_applied` (fields: `faces_detected`, `processing_ms`, `client_backend`, filenames) whenever the helper actually blurs faces, and `face_blur_skipped_reason` capturing reasons like `no_faces_detected`, `model_load_failed`, or runtime fallbacks. Events flow through `TelemetryService` and are defined in `config/telemetry.php` + `EVENT_SCHEMA_V0_2025-11-08.md`.

- **Next steps:**
  1. Wire the same helper into any future uploaders (e.g., onboarding wizard, DM attachment composer).
  2. Coordinate with backend to reject unblurred uploads once the feature graduates from beta.

## Auto-Reply Photo Game
- Goal: increase authenticity of uploads by mirroring content back to the sender.
- Detection tiering:
  1. Simple heuristics (color histograms, EXIF orientation) for MVP.
  2. Upgrade to lightweight classifier (MobileNet or CLIP) when ready.
- Reward mechanics: streak counter, leaderboard, push/email reminders.
- Safety: auto-generated replies must re-run face blur + vault encryption.

## Local-First Encrypted Vault
- Storage: IndexedDB wrapper with chunking for large blobs; consider OPFS once browser support stabilizes.
- Crypto: WebCrypto AES-GCM, keys derived from user-supplied phrase using PBKDF2 (100k iterations, salt per device) + optional hardware keystore binding.
- Sync path (future): upload encrypted blobs to S3 via presigned URL without server ever seeing plaintext; use artifact metadata only.
- UX: onboarding walkthrough emphasizing that password loss = data loss; provide manual export option.

## Outstanding Questions
1. Should quotes ever be filtered by geography or compliance rules?
2. Minimum device specs for face blur—fallback to server-side if unsupported?
3. How to moderate auto-reply content loops to avoid harassment?
4. Legal review for zero-knowledge storage promises and account recovery policy.
