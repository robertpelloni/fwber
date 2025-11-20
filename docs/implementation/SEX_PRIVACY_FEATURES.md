# Sex Quotes & Privacy Feature Notes
Date: 2025-11-20

## Dynamic Sex Quotes / Tagline System
- Component: `fwber-frontend/components/SexQuote.tsx`
- Surfaces: Home hero (`app/page.tsx`), Proximity feed (`components/ProximityFeed.tsx`), future dashboard modules.
- Content governance: maintain curated JSON, add moderation tags (NSFW, mild, humorous) for future filtering.
- Telemetry: log `quote_impression` with quote ID to measure engagement.

## Client-Side Face Blurring
- Library candidates: `@vladmandic/face-api`, `@tensorflow-models/face-landmarks-detection`, `mediapipe`.
- Flow: user selects image → canvas preview renders → faces detected → blur applied via stack blur → encrypted vault receives blurred bitmap.
- Feature flag: `FEATURE_CLIENT_FACE_BLUR` (default off until QA complete).
- QA items: confirm <500ms processing on M1 laptop, fallback message when WebGL unavailable.

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
