# FWBer Project Status & Roadmap (November 24, 2025)

This document provides a single, authoritative overview of the "fwber" project's status, features, and roadmap. It is intended to be the primary source of truth for all contributors.

## 1. Project Overview

**Concept:** "fwber" is a privacy-first, proximity-based dating and social networking application. Its core mission is to create a safer, more inclusive, and less superficial online dating experience by prioritizing user privacy and authenticity.

**Key Differentiator:** The flagship feature is "Avatar Mode," which encourages users to represent themselves with AI-generated avatars instead of actual photos. This reduces appearance-based bias and fosters connections based on personality and shared interests.

## 2. Architecture

*   **Backend:** A RESTful API built with **Laravel 12** and **PHP 8.4**.
*   **Frontend:** A responsive single-page application built with **Next.js 14**, **React 18**, and **TypeScript**.
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
*   **Safety & Security:**
    *   [x] **Geo-Spoofing Detection:** Protects against location manipulation.
    *   [x] **Moderation Dashboard:** Tools for moderators to review flagged content.
    *   [x] **Rate Limiting:** Prevents abuse and spam.
    *   [x] **Block & Report:** Users can block and report others.
*   **API & Documentation:**
    *   [x] **OpenAPI Documentation:** The backend API is extensively documented using OpenAPI (Swagger).

### Partially Implemented

These features are in progress but require further work before they are considered stable.

*   **Social & Engagement:**
    *   [ ] **Friends System:** Users can send, accept, and manage friend requests. *Backend is complete, but frontend UI needs refinement.*
    *   [ ] **Push Notifications:** Groundwork is in place, but requires full implementation and testing.
    *   [ ] **Advanced Match Filtering:** Filtering by lifestyle preferences is functional but needs more comprehensive UI controls.
*   **AI-Powered Features:**
    *   [ ] **Recommendation Engine:** The API exists but needs to be fully integrated with the frontend.
    *   [ ] **Content Generation & Optimization:** The APIs are in place but are not yet fully integrated into the user workflow.

### Not Yet Implemented

These features are planned but have not yet been started.

*   [ ] **Groups:** Functionality for users to create and join interest-based groups.
*   [ ] **Premium Features (Monetization):** Premium subscriptions, paid visibility boosts, etc.
*   [ ] **Dedicated Mobile App:** A React Native app is planned for a future phase.

## 4. Immediate Roadmap

The immediate focus is on stabilizing the existing feature set and preparing for a production release.

1.  **Stabilize Partially Implemented Features:**
    *   **Friends System:** Complete the frontend UI for managing friends and friend requests.
    *   **Push Notifications:** Fully implement and test the push notification system.
    *   **Advanced Match Filtering:** Add a more comprehensive set of UI controls for match filtering.
2.  **End-to-End Testing:**
    *   Conduct a thorough end-to-end testing pass of all core features to identify and fix any remaining bugs.
3.  **Operational Readiness:**
    *   Review and finalize all environment configurations for a production deployment.
    *   Establish a basic monitoring and alerting pipeline to track application health.

This document should be updated as the project evolves. For more detailed technical information, refer to the OpenAPI documentation and the `docs/` directory.
