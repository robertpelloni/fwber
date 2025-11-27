# Spike: Face Reveal Game & Auto-Reply Loop
_Date: 2025-11-20_

## Purpose
Outline the minimum technical approach for the “Face Reveal” game that encourages photo-for-photo engagement and optionally sends automated replies when the recipient goes silent.

## Goals
- Encourage reciprocal photo sharing by detecting the “kind” of each upload and nudging users to respond in-kind.
- Keep all experiments behind feature flags so the MVP footprint stays unchanged.
- Capture telemetry that proves the loop increases uploads and conversation depth without harming privacy guarantees.

## Core Loop
1. User receives a blurred photo preview (client blur flag already covers privacy).
2. Detection heuristics classify the image (pose, zoom, color palette, attire, environment tags).
3. App prompts the user to respond with a similar image; streak counter increments on success.
4. If the user ignores the prompt, an auto-reply bot (stock assets with faces blurred) keeps the thread alive.
5. Future phase: “Mutual face trade” unlocks once both parties consent and flip the reveal toggle.

## Technical Approach
| Area | Draft Decision |
| --- | --- |
| **Classification** | Start with lightweight heuristics (BlurHash + color histogram + simple pose estimation). Graduate to TF.js MobileNet when telemetry shows value.
| **Auto-reply assets** | Store curated, pre-blurred stock selfies in S3; responses selected via tag similarity.
| **Backend** | Dedicated controller/module for game state (streaks, cooldowns). Gated by `FEATURE_FACE_REVEAL`.
| **Frontend** | New card in DM + photo upload flows, hidden unless both backend flag and `NEXT_PUBLIC_FEATURE_FACE_REVEAL` are true.
| **Telemetry** | Events: `face_reveal_prompt_shown`, `face_reveal_response_sent`, `face_reveal_auto_reply_sent`, `face_reveal_streak_reset`.

## Privacy & Safety
- Never send the original unblurred image unless both users explicitly opt into “mutual reveal”.
- Auto-reply assets must be licensed/stock and still receive client-side blur metadata for parity.
- Incorporate moderation hooks so flagged users cannot trigger the game.

## Feature Flag Strategy
- **Backend**: `FEATURE_FACE_REVEAL` (added in `config/features.php`). Keeps any new API routes gated.
- **Frontend**: `NEXT_PUBLIC_FEATURE_FACE_REVEAL`. Hides experiment UI unless developers explicitly opt in.
- **Testing**: Cypress spec should assert that the game controls never render when the flag is false.

## Telemetry & Success Metrics
- Upload reciprocation rate (% of prompts that get a valid reply).
- Average streak length per user.
- Number of auto-replies needed per 100 prompts (should trend downward).
- Drop-off rate (users who disable the prompt after first exposure).

## Open Questions
1. Where is the classification executed? (Client-only for privacy vs. server for consistency.)
2. Should streak data live with matches or in a separate table to avoid coupling?
3. How are abusive photos handled? Does moderation happen before the auto-reply logic?
4. Does mutual reveal require real-time confirmation, or can it be asynchronous consent?

## Next Actions
- Prototype heuristic classifier + prompt UI behind both flags.
- Design DB schema for streak tracking and auto-reply queue.
- Draft telemetry schema additions in `config/telemetry.php`.
- Produce sample stock asset set with blur metadata.
