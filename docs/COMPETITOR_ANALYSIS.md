# Competitor Feature Analysis & Parity Matrix

This document provides a comprehensive comparison between **fwber** and major competitors in the dating and social networking space: **Facebook Dating**, **OkCupid**, **FetLife**, **SwingLifestyle (SLS)**, and **AdultFriendFinder (AFF)**.

## 1. Competitor Feature Overviews

### Facebook Dating
Focuses on leveraging existing social circles and "Secret Crush" mechanics.
- **Secret Crush:** Add up to 9 Facebook friends or Instagram followers; if they add you back, it's a match.
- **Groups/Events Integration:** Match with people who attend the same events or are in the same groups.
- **Second Look:** Review profiles you previously passed on.
- **Safety Features:** Share live location with a friend during a date.

### OkCupid
Famous for its deep-dive algorithmic matching via thousands of questions.
- **Question Stacks:** Answering questions to determine a "Match %".
- **Discovery Stacks:** Curated categories (e.g., "Nearby", "Pro-Choice", "Online Now").
- **Detailed Profiles:** High focus on political, religious, and lifestyle values.
- **Dealbreakers:** Hard filters for specific traits.

### FetLife
The social network for the BDSM and Kink community (Social-first, not dating-first).
- **Kink Profiles:** Detailed listing of kinks, roles, and fetishes.
- **Local Events (Munches):** Strong focus on physical meetups and community events.
- **Discussion Groups:** Extensive forum-style community interaction.
- **Vouching:** A trust-based system where community members verify each other.

### SwingLifestyle (SLS)
Focuses on the swinging/lifestyle community with a heavy emphasis on couples.
- **Couple Profiles:** Linked profiles for partners.
- **Party/Event Listings:** Detailed calendar of lifestyle parties.
- **Travel Search:** Find people and events in a specific city before you travel.
- **Hot-Or-Not:** A rating game to increase profile visibility.

### AdultFriendFinder (AFF)
High-intensity adult dating and hookup site.
- **Live Action:** Webcams and live streaming.
- **Hot-Lists:** Private lists of favorite users.
- **Groups & Communities:** Strong focus on specific sexual interests.
- **Member Blogs:** Extensive user-generated content and diaries.

---

## 2. Feature Comparison & Parity Matrix

| Feature Category | Feature | FB Dating | OkCupid | FetLife | SLS / AFF | fwber (Current) | fwber Status |
| :--- | :--- | :---: | :---: | :---: | :---: | :---: | :--- |
| **Discovery** | Proximity Search | ✅ | ✅ | ✅ | ✅ | ✅ | **Nearby Page** (Production) |
| | Matching Algorithm | ✅ | ✅ (Deep) | ❌ | ❌ | ⚠️ | **AI Matching** (Beta) |
| | Social Circle Matching | ✅ | ❌ | ❌ | ❌ | ✅ | **Friend System** (Beta) |
| | Travel Mode | ✅ | ✅ | ✅ | ✅ | 🔬 | **Travel Settings** (Experimental) |
| **Profile** | Kink/Interests | ❌ | ✅ | ✅ | ✅ | ✅ | **Lifestyle Prefs** (Production) |
| | Linked Couple Profiles | ❌ | ✅ | ✅ | ✅ | ❌ | **Planned** (Aspirational) |
| | AI Avatars/Roasts | ❌ | ❌ | ❌ | ❌ | ✅ | **Wingman AI** (Production) |
| **Communication**| Real-time Messaging | ✅ | ✅ | ⚠️ | ✅ | ✅ | **WebSocket Chat** (Production) |
| | Voice/Video Call | ✅ | ❌ | ❌ | ✅ | ✅ | **WebRTC Call** (Production) |
| | Group Chat/Rooms | ❌ | ❌ | ✅ | ✅ | ✅ | **Chatrooms** (Production) |
| **Safety** | User Vouching | ❌ | ❌ | ✅ | ✅ | ✅ | **Vouch System** (Beta) |
| | Location Fuzzing | ✅ | ❌ | ✅ | ❌ | ✅ | **Privacy Mode** (Beta) |
| | Verified Profiles | ✅ | ✅ | ⚠️ | ✅ | ✅ | **Verification** (Production) |
| **Community** | Local Events | ✅ | ❌ | ✅ | ✅ | ✅ | **Events System** (Beta) |
| | Bulletins/Pulse | ✅ | ❌ | ✅ | ✅ | ✅ | **Local Pulse** (Beta) |
| | AR Interaction | ❌ | ❌ | ❌ | ❌ | ✅ | **MatchARView** (Production) |
| **Economy** | Token Tipping | ❌ | ❌ | ❌ | ✅ | ✅ | **Token System** (Production) |
| | Pay-to-Unlock Content| ❌ | ❌ | ❌ | ✅ | 🔬 | **Photo Reveal** (Experimental) |

---

## 3. fwber's Unique Competitive Advantages

1.  **AI Wingman (Coach/Roast/Hype)**: No other major competitor has an integrated LLM-based assistant that roasts, hypes, or coaches users on their conversation style in real-time.
2.  **Augmented Reality (AR)**: The `MatchARView` and proximity-based "Safe Drops" (AR gifts/notes) are unique to fwber, bridging the gap between digital interaction and physical location.
3.  **Proximity Chatrooms**: While FetLife has "groups", fwber's chatrooms are dynamically location-aware (proximity-type), allowing users to find transient local chats based on their current GPS.
4.  **Zero-Knowledge (ZK) Location**: fwber is implementing ZK proximity proofs to allow users to verify they are "near" each other without revealing exact coordinates—a level of privacy not seen in legacy adult sites.

## 4. Gaps to Close (Priority List)

1.  **Linked Profiles (SLS/FetLife Parity)**: The lifestyle and adult communities rely heavily on couple-based accounts. fwber currently treats users as individuals.
2.  **Live Action (AFF Parity)**: Live streaming or broadcasting features are missing but highly valued in the adult community.
3.  **Deep Question Matching (OkCupid Parity)**: While fwber has AI matching, it lacks the structured "Match %" based on thousands of value-based questions that OkCupid users love.
