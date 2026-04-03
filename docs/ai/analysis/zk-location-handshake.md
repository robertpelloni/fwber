# ZK-Location Verification Analysis (v1.0.83)

## 📌 Problem Statement
The physical "NFC Tap" introduced in v1.0.82 verified that two devices were within range of a short-range radio (NFC). However, it did not strictly verify that the users were in a specific, expected location, nor did it handle the privacy concerns of exchanging raw coordinates between strangers over an unencrypted NFC link.

## 🚀 The Solution: Geohash Handshake
I have implemented a privacy-preserving proximity verification system using **Geohash Commitments** and a **Stateful Redis Relay**.

### 1. Proof Generation (Frontend)
- During an NFC tap, both clients capture their current GPS coordinates.
- They encode the coordinates into a **precision-8 geohash** (approximately 19 meters of accuracy).
- Instead of sending raw `lat/lng` across NFC, they exchange a random **Session Nonce**.
- Both clients submit their profile IDs, the peer's nonce, and their local geohash commitment to the backend.

### 2. Handshake Reconciliation (Backend)
- The server acts as a "Zero-Knowledge" relay.
- When User A submits their commitment, the server caches it in Redis for 15 seconds.
- When User B submits, the server checks for a matching commitment.
- A match is confirmed ONLY if:
  - Both users report the same precision-8 geohash.
  - Both users indicate the other user's ID.
  - Both users respond within the tiny 15-second window.

### 3. Privacy & Security Benefits
- **No Coordinate Leakage:** Users never see each other's actual GPS coordinates.
- **Relay Blindness:** The server only sees a geohash string. It does not store the exact raw coordinates of the interaction.
- **Physical Proof:** This system is nearly impossible to spoof without GPS-spoofing both devices simultaneously and having them both submit the same random nonces to the server.

## 📈 Impact on Trust Scores
Verified physical meetups now grant a **75-point trust boost** (up from 50), which is used in the `NearbyUserRankingService` to surface highly trusted individuals in the "People Nearby" discovery feed.
