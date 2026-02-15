# fwber Project Status & Roadmap

This document provides a single, authoritative overview of the "fwber" project's status, features, and roadmap. It is intended to be the primary source of truth for all contributors.

## 1. Project Overview

**Current Status:** PRE-DEPLOYMENT STAGING  
**Version:** v0.3.34
**Last Updated:** February 06, 2026

**Concept:** "fwber" is a privacy-first, proximity-based dating and social networking application. Its core mission is to create a safer, more inclusive, and less superficial online dating experience by prioritizing user privacy and authenticity.

## 2. Architecture

*   **Backend:** Laravel 12 API (PHP 8.4)
*   **Frontend:** Next.js 16 (React 18)
*   **Crypto:** Solana Blockchain
*   **Real-time:** Laravel Reverb + Echo (Unified WebSocket)
*   **Infrastructure:** Docker, Redis, MySQL 8.0

## 3. Feature Completion Status

**Overall Completion:** 100% of MVP & Phase 4 Features

| Module | Status | Notes |
|--------|--------|-------|
| **Auth & Onboarding** | ✅ Complete | Includes 2FA, Physical Profile, Identity Verification |
| **Social Graph** | ✅ Complete | Friends, Groups, Chatrooms, Events |
| **Discovery** | ✅ Complete | Swipe, Map/AR, Proximity Feed, Group Matching |
| **Communication** | ✅ Complete | Chat, Video (WebRTC), Voice, E2E Encryption |
| **Viral/AI** | ✅ Complete | Wingman, Roast, Fortune, Share-to-Unlock |
| **Economy** | ✅ Complete | Wallet, Tokens, Gifts, Merchant Portal |
| **Admin/Ops** | ✅ Complete | Dashboard, Logs, Moderation, Analytics, Sentry |

## 4. Recent Milestones (v0.3.34)

*   **System Dashboard:** Real-time health monitoring linked to backend.
*   **Merchant Analytics:** Integrated real backend data for KPI tracking.
*   **Refactoring:** Removed legacy Mercure dependencies; unified on WebSocket hook.
*   **Documentation:** Created `VISION.md`, `UNIVERSAL_LLM_INSTRUCTIONS.md`, and Handoff docs.

### Feature Summary (Cumulative)
*   **Core:**
    *   [x] **User Authentication:** Secure user registration, login, and session management.
    *   [x] **AI Avatar Generation:** Users can create and use AI-generated avatars for their profiles.
    *   [x] **Profile Management:** Create, edit, and view user profiles.
    *   [x] **Matching System:** The core "swipe" and matching logic is functional.
    *   [x] **Direct Messaging:** Real-time one-on-one chat between matched users.
    *   [x] **Help Center:** Comprehensive user documentation integrated into the frontend (`/help`).
*   **Crypto Economy (Fully Integrated):**
    *   [x] **Trojan Horse Integration:** Internal ledger for instant/free micro-transactions.
    *   [x] **Solana Bridge:** Users can withdraw accumulated tokens to external wallets (Phantom, Solflare).
    *   [x] **Merchant API:** "Pay with fwber" infrastructure allowing third-party sites to accept FWB tokens.
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
    *   [x] **AR View:** Augmented Reality camera view for locating nearby drops and events overlayed on the real world.
    *   [x] **Paid Photo Reveals:** Token-based photo unlock with blurred preview and inline payment confirmation.
    *   [x] **Content Unlock System:** Generic `ContentUnlockGate` component for premium feature gating.
    *   [x] **Merchant Promotions:** "Local Deals" discovery UI with map/list views and category filtering.
    *   [x] **Matchmaker Bounties:** Bounty browsing and submission UI with filters.
*   **Viral & Engagement:**
    *   [x] **"Roast My Profile":** Viral marketing tool allowing users to generate shareable AI roasts/hype of their profiles.
    *   [x] **Friends System:** Send, accept, manage friends, and send tips.
    *   [x] **Push Notifications:** Real-time alerts for Tips, Friend Requests, and Matches (WebPush/VAPID).
    *   [x] **Groups:** Create and join public/private/paid groups.
    *   [x] **Gamification:** Daily Streak tracking (UI & Backend Logic) and Community Leaderboards.
    *   [x] **Achievements System:** Full gamification UI with progress tracking and rewards.
    *   [x] **Vouch System:** Social proof system allowing friends to vouch for users.
    *   [x] **Voice Messages:** Record and send voice notes in chat (with transcription support).
    *   [x] **Feedback Loop:** In-app feedback submission with AI categorization and sentiment analysis.
    *   [x] **Share to Unlock (Viral):** Users can unlock premium features by sharing content (e.g. Roasts, Profile links) and getting views.
    *   [x] **Proximity Chatrooms:** Full discovery and chat UI with token-gated entry support.
    *   [x] **Bulletin Boards:** Location-based community discussion boards.
    *   [x] **Profile View Tracking:** "Who Viewed Me" feature with analytics and stats.
*   **Operational Excellence:**
    *   [x] **Sentry Integration:** Error tracking for Frontend and Backend.
    *   [x] **APM:** Basic Application Performance Monitoring (Slow Request logging).
    *   [x] **PWA Polish:** Verified manifest, icons, and service worker for full installability.
    *   [x] **End-to-End Testing:** Cypress tests for critical paths (Auth, Matching, Messaging, Wallet, Match Insights).
*   **Safety & Security:**
    *   [x] **Geo-Spoofing Detection:** Protects against location manipulation.
    *   [x] **Moderation Dashboard:** Tools for moderators to review flagged content.
    *   [x] **Rate Limiting:** Prevents abuse and spam.
    *   [x] **Block & Report:** Users can block and report others.
    *   [x] **Backend Hardening:** Strict CSP, Restricted CORS, Safe .env defaults.
    *   [x] **Frontend Build:** Turbopack disabled for stability.
*   **API & Documentation:**
    *   [x] **OpenAPI Documentation:** The backend API is extensively documented using OpenAPI (Swagger).
    *   [x] **Advanced Match Filtering:** Filtering by lifestyle preferences (Diet, Politics, etc.) is fully functional and token-gated for premium users.
    *   [x] **AI Wingman:** Real-time chat suggestions, ice-breakers, and profile analysis.
    *   [x] **Group Matching:** Find and match with other friend groups for shared events.
    *   [x] **Event Discussions:** Real-time chat threads specific to shared events.
    *   [x] **Shared Event Invitations:** Invite friends and groups to shared events.

## 5. Next Steps (Phase 5)

Focus shifts from **Feature Development** to **Scale & Stability**.

*   [ ] **Load Testing:** Simulate 10k concurrent users.
*   [ ] **Infrastructure:** Finalize Kubernetes configs.
*   [ ] **Optimization:** Query tuning based on new logs.
*   [ ] **Redis Caching:** Performance optimization
*   [ ] **Feature Flag Rollout:** Gradual production enablement

See `docs/ROADMAP.md` for detailed future plans.

## 6. Immediate Roadmap (Previous)

### Phase 4B: Frontend UI Implementation (Completed Jan 10, 2026)

**Critical - Must Have:**
| Feature | Backend | Frontend | Priority | Effort |
|---------|---------|----------|----------|--------|
| Achievements UI | ✅ Complete | ✅ Complete | Critical | Done |
| Proximity Chatrooms | ✅ Complete | ✅ Complete | Critical | Done |
| Paid Photo Reveals | ✅ Complete | ✅ Complete | Critical | Done |
| Help Center | - | ✅ Complete | Critical | Done |

### Phase 4C: Technical Debt (Ongoing)

*   [ ] **Feature Flags:** Enable `chatrooms`, `recommendations`, `ai_wingman`, `video_chat` in production
*   [ ] **Mock Replacements:** Replace mock content safety with real AWS Rekognition/Google Vision
*   [ ] **Sentry Integration:** Re-enable commented-out error tracking in frontend
*   [ ] **Commented Routes:** Re-enable WebSocket and Bulletin Board routes
*   [ ] **Legacy Cleanup:** Remove Mercure references (migrated to Reverb Dec 2025)

### Key References

*   **Master Plan:** `docs/DEVELOPMENT_MASTER_PLAN.md`
*   **Feature Flags:** `docs/FEATURE_FLAGS.md`
*   **API Docs:** OpenAPI/Swagger
*   **Architecture:** `docs/PROJECT_STRUCTURE.md`
