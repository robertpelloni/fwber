# fwber Project Status & Roadmap

This document provides a single, authoritative overview of the "fwber" project's status, features, and roadmap. It is intended to be the primary source of truth for all contributors.

## 1. Project Overview

**Current Status:** PRE-DEPLOYMENT STAGING (Phase 5 Active)
**Version:** v0.3.35
**Last Updated:** February 18, 2026

**Concept:** "fwber" is a privacy-first, proximity-based dating and social networking application. Its core mission is to create a safer, more inclusive, and less superficial online dating experience by prioritizing user privacy and authenticity.

## 2. Architecture

*   **Backend:** Laravel 12 API (PHP 8.4)
*   **Frontend:** Next.js 16 (React 18)
*   **Crypto:** Solana Blockchain
*   **Real-time:** Laravel Reverb + Echo (Unified WebSocket)
*   **Infrastructure:** Docker, Kubernetes (Manifests Ready), Redis, MySQL 8.0

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

## 4. Recent Milestones (v0.3.35)

*   **UI Self-Documentation:** Added comprehensive tooltips to Registration, Physical Profile, and Wallet.
*   **Help Center:** Expanded with guides for Events, Merchant, and Safety.
*   **Performance:** Established load testing baseline (55 req/sec on dev environment).
*   **Infrastructure:** Created Kubernetes manifests (`docker/k8s/`).

## 5. Next Steps (Phase 5)

Focus shifts from **Feature Development** to **Scale & Stability**.

*   [x] **Load Testing:** Run baseline load test.
*   [x] **Infrastructure:** Finalize Kubernetes configs.
*   [ ] **Optimization:** Query tuning based on load test logs.
*   [ ] **Production Deploy:** Deploy to staging cluster.

See `docs/ROADMAP.md` for detailed future plans.
