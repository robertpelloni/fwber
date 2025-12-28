# FWBer Project Status & Roadmap

This document provides a single, authoritative overview of the "fwber" project's status, features, and roadmap. It is intended to be the primary source of truth for all contributors.

## 1. Project Overview

**Concept:** "fwber" is a privacy-first, proximity-based dating and social networking application. Its core mission is to create a safer, more inclusive, and less superficial online dating experience by prioritizing user privacy and authenticity.

**Key Differentiator:** The flagship feature is "Avatar Mode," which encourages users to represent themselves with AI-generated avatars instead of actual photos. This reduces appearance-based bias and fosters connections based on personality and shared interests.

## 2. Architecture

*   **Backend:** A RESTful API built with **Laravel 12** and **PHP 8.4**.
*   **Frontend:** A responsive single-page application built with **Next.js 14**, **React 18**, and **TypeScript**.
*   **Crypto:** **Solana Blockchain** integration (Devnet/Mainnet) via `@solana/web3.js`.
*   **Real-time:** **Mercure** (built on Server-Sent Events) powers real-time chat, notifications, and location updates.
*   **Database:** **MySQL 8.0**, with **SQLite** for testing and local development.
*   **Environment:** The entire stack is containerized using **Docker**.

## 3. MVP Feature Status

The primary objective is to deliver a stable MVP. The features listed below are categorized by their implementation status.

### Completed & Deployed

These features are considered complete and are ready for a production environment.

*   **Core:**
    *   [x] **User Authentication:** Secure user registration, login, and session management.
    *   [x] **AI Avatar Generation:** Users can create and use AI-generated avatars for their profiles.
    *   [x] **Profile Management:** Create, edit, and view user profiles.
    *   [x] **Matching System:** The core "swipe" and matching logic is functional.
    *   [x] **Direct Messaging:** Real-time one-on-one chat between matched users.
*   **Crypto Economy (Fully Integrated):**
    *   [x] **Trojan Horse Integration:** Internal ledger for instant/free micro-transactions.
    *   [x] **Solana Bridge:** Users can withdraw accumulated tokens to external wallets (Phantom, Solflare).
    *   [x] **Merchant API:** "Pay with FWBer" infrastructure allowing third-party sites to accept FWB tokens.
    *   [x] **Crypto Deposits:** Users can deposit SOL/SPL tokens from external wallets to their internal ledger.
    *   [x] **Wallet Dashboard:** PWA-ready wallet UI with QR code deposits and transaction history.
    *   [x] **Tokenomics:** Signup bonuses, Referral Rewards, and Daily Login Bonuses.
    *   [x] **P2P Transfers:** Send tokens with messages via Wallet, Chat, or Profile.
    *   [x] **Creator Subscriptions:** Monthly recurring token payments to unlock all private content from a creator.
    *   [x] **Token-Gated Content:** Pay-to-unlock private photos and paid events.
    *   [x] **Paid Groups:** Token entry fees for exclusive communities.
    *   [x] **Paid AI Features:** Token costs for Profile Generation, Conversation Starters, and Optimization.
    *   [x] **Paid Match Insights:** Token costs to unlock detailed AI compatibility reports and explanations.
    *   [x] **Payment Requests:** "Venmo-style" invoicing allowing users to request tokens from friends and matches.
    *   [x] **Geo-Fenced Token Drops:** Users can drop tokens at physical locations for others to find and claim ("Pokemon GO" style).
*   **Social & Engagement:**
    *   [x] **Friends System:** Send, accept, manage friends, and send tips.
    *   [x] **Push Notifications:** Real-time alerts for Tips, Friend Requests, and Matches (WebPush/VAPID).
    *   [x] **Groups:** Create and join public/private/paid groups.
    *   [x] **Gamification:** Daily Streak tracking (UI & Backend Logic) and Community Leaderboards.
    *   [x] **Vouch System:** Social proof system allowing friends to vouch for users.
*   **Safety & Security:**
    *   [x] **Geo-Spoofing Detection:** Protects against location manipulation.
    *   [x] **Moderation Dashboard:** Tools for moderators to review flagged content.
    *   [x] **Rate Limiting:** Prevents abuse and spam.
    *   [x] **Block & Report:** Users can block and report others.
*   **API & Documentation:**
    *   [x] **OpenAPI Documentation:** The backend API is extensively documented using OpenAPI (Swagger).
    *   [x] **Advanced Match Filtering:** Filtering by lifestyle preferences (Diet, Politics, etc.) is fully functional and token-gated for premium users.

### Partially Implemented

These features are in progress but require further work before they are considered stable.

*   **AI-Powered Features:**
    *   [x] **Recommendation Engine:** Fully integrated with "For You" feed and backend AI logic.

### Not Yet Implemented

These features are planned but have not yet been started.

*   [ ] **Premium Features (Fiat):** Traditional fiat subscriptions (Stripe) alongside token payments.
*   [ ] **Dedicated Mobile App:** A React Native app is planned for a future phase (PWA currently serves this role).

## 4. Immediate Roadmap

The immediate focus is on stabilizing the existing feature set and preparing for a production release.

1.  **End-to-End Testing:**
    *   Conduct a thorough end-to-end testing pass of all core features to identify and fix any remaining bugs.
2.  **Operational Readiness:**
    *   Review and finalize all environment configurations for a production deployment.
    *   Establish a basic monitoring and alerting pipeline to track application health.

This document should be updated as the project evolves. For more detailed technical information, refer to the OpenAPI documentation and the `docs/` directory.
