# Spike: Local-First Encrypted Media Vault
_Date: 2025-11-20_

## Purpose
Define the architectural guardrails for storing user media locally (or as encrypted blobs) so that sensitive photos never reside on the server in plaintext.

## Objectives
- Support an opt-in vault experience where media is encrypted on-device with user-supplied secrets.
- Keep the entire feature behind `FEATURE_LOCAL_MEDIA_VAULT` (backend) and `NEXT_PUBLIC_FEATURE_LOCAL_MEDIA_VAULT` (frontend).
- Ensure the flow composes with client face blur so privacy upgrades stack predictably.

## Constraints & Assumptions
- Browsers have ~50–200MB practical IndexedDB/OPFS quotas on mobile; desktop allows ~2GB.
- No password recovery: if the user forgets their vault key, vault contents are gone.
- Upload flows must still support non-vault paths for the MVP toggle.

## Proposed Architecture
| Layer | Draft Plan |
| --- | --- |
| **Storage** | IndexedDB (Dexie wrapper) to start; graduate to OPFS when browser adoption is higher. Chunk large files to avoid quota spikes.
| **Encryption** | WebCrypto AES-GCM with per-device key derived from passphrase via PBKDF2 (100k iterations + per-device salt). Store salts + metadata in localStorage.
| **Sync (optional)** | Phase 1: no sync. Phase 2: encrypt blobs locally, upload to S3 via presigned URL, store only ciphertext + metadata server-side.
| **Key Management** | Prompt users for a vault phrase during onboarding when the flag is enabled. Offer manual export/import JSON bundle for migrations.
| **Integration** | `PhotoUpload` optionally writes blurred+encrypted file to vault before hitting the network. Gallery surfaces read from vault first, fallback to server.

## Security Considerations
- Enforce minimum passphrase length + entropy checks.
- Wipe decrypted blobs from memory as soon as previews finish rendering.
- Provide explicit warnings that clearing browser data deletes the vault.
- Run threat modeling for shared devices and kiosk logins.

## Feature Flag Strategy
- Backend routes (e.g., `/api/vault/*`) stay disabled unless `FEATURE_LOCAL_MEDIA_VAULT=true`.
- Frontend UI (vault onboarding, settings toggle, vault gallery) hidden unless `NEXT_PUBLIC_FEATURE_LOCAL_MEDIA_VAULT=true`.
- Add docs + Cypress smoke tests ensuring the beta toggle can be exercised safely.

## Telemetry & Observability
- Events: `vault_enabled`, `vault_disabled`, `vault_media_added`, `vault_media_restored`, `vault_export_generated`.
- Metrics should never expose plaintext metadata—only counts and sizes.

## Open Questions
1. Should we require hardware keystore (WebAuthn) for unlocking on capable devices?
2. How do we migrate existing uploaded media into the vault without re-uploading?
3. What happens when users downgrade devices or uninstall the app?
4. Can we support partial sync (thumbnails only) without leaking sensitive data?

## Next Steps
- Build a thin proof-of-concept service that writes/reads encrypted blobs from IndexedDB.
- Define API stubs for encrypted blob upload/download (even if disabled) to validate signatures.
- Document legal/compliance implications (data export, law enforcement requests).
