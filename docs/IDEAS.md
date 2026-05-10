# Ideation & Architecture Refinement Log

## Systemic Refactoring Candidates
*   **Redis-backed Session State:** Moving all volatile state (like the verification store) entirely to Redis ensures the backend scales horizontally without sticky sessions.
*   **WebRTC for Video:** The current video chat relies on WebSockets for signaling. Implementing robust STUN/TURN server configurations (like Coturn) is necessary for reliable peer-to-peer connectivity across NATs.
*   **GraphQL Migration (Optional but beneficial):** Transitioning highly relational queries (like user profiles with achievements, matches, and inventory) to a GraphQL endpoint would prevent over-fetching on the frontend and simplify state management.
*   **Federation Enhancement:** The ActivityPub implementation is foundational. Expanding it to fully support Inbox/Outbox forwarding and richer object types (like Articles for the bulletin board) would make "fwber" a true Fediverse citizen.
*   **AI Rate Limiting:** The Wingman AI is powerful but costly. Implementing token-bucket rate limiting specifically for LLM-backed endpoints (tied to the user's internal wallet balance) is crucial for economic sustainability.

## Feature Innovations
*   **AR Proximity (Geo-AR):** Since location data is central, integrating a library like AR.js or 8th Wall could allow users to view nearby "fwber" events or verified merchants through their device camera in augmented reality.
*   **Decentralized Identity (DID):** Moving beyond basic email/password auth to support verifiable credentials (W3C DIDs) would align perfectly with the "Radical Privacy" vision, allowing users to own their identity graphs.
*   **Smart Contracts for Merchants:** If "fwber" adopts a web3 stance, merchant promotions and referral rewards could be codified as smart contracts on a low-fee chain (like Polygon or Solana), ensuring transparent payout mechanics.
*   **Hyper-Local Mesh Networking:** For very dense environments (like concerts or festivals), integrating Bluetooth Low Energy (BLE) mesh networking via native mobile plugins could allow proximity matching even when cellular service drops.
