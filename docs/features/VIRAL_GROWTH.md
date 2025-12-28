# Viral Growth Features

This document outlines the viral growth features implemented in FWBer to drive user acquisition and engagement.

## Overview

The viral growth strategy relies on incentivizing users to share the app with friends in exchange for unlocking content. Instead of paying for premium features immediately, users can "Unlock via Share" to access specific insights or profiles.

## Implemented Features

### 1. "Who Likes You" Teasers

**Goal:** Drive shares by letting users see who liked them without paying for Gold.

**Mechanism:**
*   Users visit the "Who Likes You" page (`/premium/who-likes-you`).
*   Instead of a completely empty state or a hard paywall, users see a list of "blurred" profiles.
*   Each blurred profile shows minimal teaser info (e.g., "Someone", "24 years old").
*   Clicking "Unlock" on a blurred profile triggers the `ShareToUnlock` component.
*   The user must share the app (copy link, Twitter, Facebook) to unlock that specific profile.
*   Once shared, the profile is permanently revealed for that user.

**Technical Implementation:**
*   **Backend:** `PremiumController::getWhoLikesYou` now returns a sanitized list of likers. If a user is not premium, sensitive data (name, photos) is hidden for locked profiles.
*   **Unlock Tracking:** The `ShareUnlockController` and `ShareUnlock` model track which profiles a user has unlocked via sharing.
*   **Frontend:** The `WhoLikesYouPage` renders `ShareToUnlock` for locked profiles. Upon successful share, the list is refetched to show the revealed data.

### 2. Cosmic Match & Nemesis Finder Unlock

**Goal:** Leverage the popularity of astrology and "anti-matches" to encourage sharing.

**Mechanism:**
*   **Cosmic Match:** The "Worst Match" (Avoid) section is blurred. Users must share to see which sign they should avoid.
*   **Nemesis Finder:** The detailed "Scientific Analysis" of why someone is a nemesis is locked. Users share to read the roast/analysis.

**Technical Implementation:**
*   **Components:** `CosmicMatch.tsx` and `NemesisFinder.tsx` wrap specific sections in `ShareToUnlock`.
*   **Unlock Logic:** Currently uses `localStorage` for immediate client-side feedback, but conceptually links to the same backend tracking.

## Future Viral Features (Planned)

*   **"Roast Your Date" Sharing:** Allow users to generate a roast of a profile and share a public link to it (with the target's identity obscured or if they are friends).
*   **"Vouch for Me" Campaigns:** Users ask friends to "vouch" for them to gain trust badges or tokens.
*   **Referral Leaderboards:** Gamified tracking of who has brought the most users to the app.

## Data Structures

### ShareUnlock Model
*   `user_id`: The user performing the unlock.
*   `target_profile_id`: The profile or content ID being unlocked.
*   `platform`: The platform used for sharing (e.g., 'copy', 'twitter').
*   `created_at`: Timestamp.

## API Endpoints

*   `POST /api/share-unlock`: Records a share action and unlocks specific content.
*   `GET /api/premium/who-likes-you`: Returns the list of likers with lock status based on premium subscription OR share history.
