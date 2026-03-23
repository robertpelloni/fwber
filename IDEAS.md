# IDEAS.md — fwber Concept Lab

> **Brainstorming, Pivots, Refactors, and Expansions**
> *Generated via comprehensive architectural audit on 2026-03-23*

This document serves as a staging ground for ideas that extend beyond the current roadmap. It includes potential architecture shifts, new business models, and deep technical refactors.

---

## 🛠️ Deep Technical Refactors

### 1. Unified Event Sourcing Architecture
**Current State:** The backend relies heavily on CRUD operations with scattered event dispatching (`ProximityArtifactEvent`, `VideoSignal`, etc.).
**The Idea:** Transition the entire core interaction model (likes, passes, messages, profile updates) to an Event Sourced architecture. 
**Why:**
- Provides a perfect audit log for moderation and dispute resolution.
- Makes the "AI Wingman" incredibly smart by allowing it to "replay" the history of an interaction to understand context perfectly.
- Simplifies offline sync (CRDTs) by allowing clients to sync event logs rather than state diffs.

### 2. Rust-Native Real-Time Broadcasting
**Current State:** Laravel Reverb handles WebSockets. `fwber-geo` (Rust) handles spatial indexing.
**The Idea:** Move the WebSocket broadcasting layer entirely into the Rust `fwber-geo` service (or a sibling service).
**Why:**
- Reverb is excellent, but PHP inherently struggles with holding hundreds of thousands of concurrent open connections in extremely dense environments (e.g., a music festival). Rust + Tokio/Actix is designed exactly for this.
- The Rust service already holds the in-memory spatial grid. It could instantly broadcast "User X entered your cell" without making a round-trip to Redis/Laravel.

### 3. GraphQL Migration for the Client API
**Current State:** REST API endpoints.
**The Idea:** Implement a GraphQL layer (Lighthouse for Laravel).
**Why:**
- The mobile wrapper and Next.js frontend currently over-fetch data (e.g., pulling full profile objects when only the avatar and name are needed in a list).
- Simplifies state management on the client side (using Apollo or Relay).

---

## 🚀 Product Pivots & Expansions

### 4. B2B "Local Pulse" API (Data Monetization)
**Current State:** Local Pulse is purely for users.
**The Idea:** Package the aggregated, anonymized sentiment and demographic data from the Local Pulse and sell it to local businesses via a dedicated API.
**Why:**
- If a bar can see that the local pulse in a 3-block radius is "80% single, looking for a chill vibe, high energy," they can instantly trigger a targeted promotion (which we already built the infrastructure for).

### 5. "Anti-App" Hardware Token
**Current State:** Relies on smartphones.
**The Idea:** Develop a cheap, Bluetooth Low Energy (BLE) or NFC hardware token (like a keychain or bracelet) that acts as the proximity beacon.
**Why:**
- Fully realizes the "get off your screen" ethos. The app stays closed in your pocket. The token vibrates or glows when a high-compatibility match is within 50 feet. You only open the app to see who it is.

### 6. ActivityPub "Dating" Namespace
**Current State:** We support standard `Note` and `Follow` activities for public posts.
**The Idea:** Propose and implement a custom ActivityPub namespace extensions for dating semantics (e.g., `fwber:Flirt`, `fwber:VibeCheck`).
**Why:**
- Allows users on fwber to "match" with users on other compatible fediverse dating apps, creating the first open, interoperable dating network.

---

## 🎨 UI/UX Polish & Features

### 7. AR "Ghost" Navigation
**Current State:** Matches are shown on a 2D map.
**The Idea:** Implement a WebXR or React Native AR view that shows a glowing "aura" or breadcrumb trail leading to a match in a crowded venue.
**Why:**
- Solves the "I'm by the bar, wearing a black shirt" problem in dark, crowded environments.

### 8. Voice-Only "Confessional" Mode
**Current State:** Audio rooms exist, but profiles are text/image.
**The Idea:** A mode where profiles are entirely disabled. Users can only upload a 15-second voice memo. Matches are made purely on the sound of a voice and the content of the memo.
**Why:**
- Highly engaging, low-friction, and plays perfectly into the privacy-first, anti-superficial brand identity.

### 9. Gamified "Wingman" Bounties
**Current State:** Basic token rewards for referrals.
**The Idea:** Users can place a "bounty" of FWB tokens on themselves. If a friend acts as a wingman and successfully sets them up on a date that leads to a positive review, the wingman gets the bounty.
**Why:**
- Creates a massive viral loop. People love playing matchmaker for their friends.

---
*End of Ideation Document*