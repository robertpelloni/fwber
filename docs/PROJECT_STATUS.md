# fwber Project Status & Roadmap

This document provides a single, authoritative overview of the "fwber" project's status, features, and roadmap. It is intended to be the primary source of truth for all contributors.

## 1. Project Overview

**Current Status:** PRE-DEPLOYMENT STAGING  
**Version:** v0.3.26  
**Last Updated:** January 9, 2026

**Concept:** "fwber" is a privacy-first, proximity-based dating and social networking application. Its core mission is to create a safer, more inclusive, and less superficial online dating experience by prioritizing user privacy and authenticity.

**Key Differentiator:** The flagship feature is "Avatar Mode," which encourages users to represent themselves with AI-generated avatars instead of actual photos. This reduces appearance-based bias and fosters connections based on personality and shared interests.

## 2. Architecture

*   **Backend:** A RESTful API built with **Laravel 12** and **PHP 8.4** (78 controllers, 53 services).
*   **Frontend:** A responsive single-page application built with **Next.js 16**, **React 18**, and **TypeScript** (18+ pages, 150+ components).
*   **Crypto:** **Solana Blockchain** integration (Devnet/Mainnet) via `@solana/web3.js`.
*   **Real-time:** **Laravel Reverb + Echo** powers real-time chat, notifications, and location updates (migrated from Mercure → Pusher Dec 2025).
*   **Database:** **MySQL 8.0** with Spatial extensions, **SQLite** for testing.
*   **Environment:** The entire stack is containerized using **Docker**.
*   **Testing:** 200+ test files with comprehensive coverage.

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
*   **Viral & Engagement:**
    *   [x] **"Roast My Profile":** Viral marketing tool allowing users to generate shareable AI roasts/hype of their profiles.
    *   [x] **Friends System:** Send, accept, manage friends, and send tips.
    *   [x] **Push Notifications:** Real-time alerts for Tips, Friend Requests, and Matches (WebPush/VAPID).
    *   [x] **Groups:** Create and join public/private/paid groups.
    *   [x] **Gamification:** Daily Streak tracking (UI & Backend Logic) and Community Leaderboards.
    *   [x] **Vouch System:** Social proof system allowing friends to vouch for users.
    *   [x] **Voice Messages:** Record and send voice notes in chat (with transcription support).
    *   [x] **Feedback Loop:** In-app feedback submission with AI categorization and sentiment analysis.
    *   [x] **Share to Unlock (Viral):** Users can unlock premium features by sharing content (e.g. Roasts, Profile links) and getting views.
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

### Backend Complete - Frontend Missing

These features have complete backend implementations but lack frontend UI. See `docs/MISSING_FEATURES.md` for full details.

**Critical Priority (High User Impact):**
*   [ ] **Achievements System:** Full gamification backend (achievements, categories, token rewards) - needs profile/dashboard UI
*   [ ] **Proximity Chatrooms Discovery:** Full API exists - needs browsing/create/join UI
*   [ ] **Bulletin Boards:** Full threaded discussion API - needs discovery and posting UI
*   [ ] **Profile View Tracking:** "Who viewed you" feature - needs dedicated page

**High Priority (Revenue Impact):**
*   [ ] **Paid Photo Reveals:** Token-based photo unlock - needs blurred preview + payment UI
*   [ ] **Content Unlock System:** Generic premium content framework - needs gate components
*   [ ] **Merchant Promotions Discovery:** API complete - needs consumer browsing/map UI

**Medium Priority (Engagement Impact):**
*   [ ] **Share-to-Unlock:** Viral growth feature - needs share buttons + progress UI
*   [ ] **Message Reactions:** Placeholder in chatrooms - needs reaction picker
*   [ ] **Group-to-Group Matching:** Full backend - needs discovery/match UI
*   [ ] **Matchmaker Bounties:** Token rewards for introductions - needs bounty browser
*   [ ] **Extended Viral Content:** Fortunes, cosmic matches, nemesis finder - only roasts have UI

**Low Priority (Future):**
*   [ ] **E2E Encryption:** Key infrastructure exists - needs UI for key management
*   [ ] **Premium Features (Fiat):** Stripe subscriptions (LOW - token economy preferred)
*   [ ] **Dedicated Mobile App:** React Native (LOW - PWA serves role)

## 4. Immediate Roadmap

### Phase 4A: Documentation & Analysis (Completed Jan 9, 2026)

*   [x] **Comprehensive Codebase Analysis:** 78 controllers, 53 services, 200+ tests mapped
*   [x] **DEVELOPMENT_MASTER_PLAN.md:** Created authoritative reference document
*   [x] **Monorepo Structure:** Verified and documented (no submodules)
*   [x] **Context Standardization:** `AGENTS.md` files for all critical subdirectories
*   [x] **Test Coverage Audit:** No critical gaps found

### Phase 4B: Frontend UI Implementation (Current Focus - Jan 2026)

**Critical - Must Have:**
| Feature | Backend | Frontend | Priority | Effort |
|---------|---------|----------|----------|--------|
| Achievements UI | ✅ Complete | ❌ Missing | Critical | Medium |
| Proximity Chatrooms | ✅ Complete | 🔶 Partial | Critical | High |
| Paid Photo Reveals | ✅ Complete | ❌ Missing | Critical | Low |

**High - Should Have:**
| Feature | Backend | Frontend | Priority | Effort |
|---------|---------|----------|----------|--------|
| Share-to-Unlock | ✅ Complete | ❌ Missing | High | Medium |
| Merchant Discovery | ✅ Complete | 🔶 Partial | High | Medium |
| Profile Views | ✅ Complete | ❌ Missing | High | Low |

**Medium - Nice to Have:**
| Feature | Backend | Frontend | Priority | Effort |
|---------|---------|----------|----------|--------|
| Bulletin Boards | ✅ Complete | ❌ Missing | Medium | Medium |
| Message Reactions | 🔶 Partial | ❌ Placeholder | Medium | Low |
| Group Matching UI | ✅ Complete | ❌ Missing | Medium | Medium |
| Extended Viral (Fortunes, etc.) | ✅ Complete | ❌ Missing | Medium | Low |

### Phase 4C: Technical Debt (Ongoing)

*   [ ] **Feature Flags:** Enable `chatrooms`, `recommendations`, `ai_wingman`, `video_chat` in production
*   [ ] **Mock Replacements:** Replace mock content safety with real AWS Rekognition/Google Vision
*   [ ] **Sentry Integration:** Re-enable commented-out error tracking in frontend
*   [ ] **Commented Routes:** Re-enable WebSocket and Bulletin Board routes
*   [ ] **Legacy Cleanup:** Remove Mercure references (migrated to Reverb Dec 2025)

### Phase 5: Scale & Production

*   [ ] **Kubernetes Deployment:** Container orchestration
*   [ ] **Horizontal Scaling:** Load balancing strategy
*   [ ] **Redis Caching:** Performance optimization
*   [ ] **Feature Flag Rollout:** Gradual production enablement

### Key References

*   **Master Plan:** `docs/DEVELOPMENT_MASTER_PLAN.md`
*   **Feature Flags:** `docs/FEATURE_FLAGS.md`
*   **API Docs:** OpenAPI/Swagger
*   **Architecture:** `docs/PROJECT_STRUCTURE.md`

This document should be updated as the project evolves. For more detailed technical information, refer to the OpenAPI documentation and the `docs/` directory.
