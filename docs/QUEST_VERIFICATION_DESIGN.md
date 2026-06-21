# Quest ZK/NFC Verification Design

## Goal
Require cryptographic proof (Zero-Knowledge / NFC scan) for a user to claim a quest reward, rather than a simple unverified button click.

## Mechanics
1. **NFC / ZK Seed**:
   - A physical location or merchant issues a cryptographic "seed" (which could be an NFC tag UID + a daily rotating secret).
   - For our implementation, a `verification_secret` will be added to the `quests` table.

2. **Client-side Proof Generation**:
   - The user "scans" an NFC tag (or clicks "Generate Proof" in the UI for our simulation).
   - The client generates a SHA-256 hash combining the `quest.id`, `user.id`, and the scanned `verification_secret`.

3. **Backend Validation (`/api/quests/:id/verify`)**:
   - Instead of just `POST /complete`, the client sends `POST /verify` with `{ payload: "<generated_hash>" }`.
   - The backend re-computes the expected hash: `sha256(quest.id + user.id + quest.verification_secret)`.
   - If `computed_hash === payload`, the quest is verified and rewards are granted.

## Database Changes
- Add `verification_secret String? @db.VarChar(255)` to `quests`.
- Make it required for ZK quests, null for generic quests.

## API Changes
- Update `POST /quests/:id/complete` to expect an optional `proof` body parameter.
- If the quest requires a secret, the proof MUST match.
