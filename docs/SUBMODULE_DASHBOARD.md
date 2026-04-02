# FWBER Submodule & Repository Dashboard

> **Generated on:** 2026-04-02
> **Current Global Version:** 1.0.75

This dashboard lists all logical sub-packages, submodules, and referenced projects within the `fwber` repository. It explains the project directory structure to ensure all AI agents have immediate situational awareness.

## 🗂️ Project Directory Structure

```text
fwber/
├── docs/                      # Global documentation, AI instructions, architecture diagrams, testing strategies
├── fwber-backend/             # Laravel 12 (PHP) core API, models, queues, and federation logic
├── fwber-frontend/            # Next.js 16 (React) user-facing platform, real-time presence UI
├── fwber-geo/                 # Rust microservice for high-speed geofencing and proximity calculations
├── mobile/                    # React Native / Expo application for iOS/Android
├── submodules/                # Directory for external git submodules (currently empty/monorepo managed)
├── context_portal/            # Alembic/DB portal for context management
├── .borg/                     # AI memory layer and context storage (LanceDB, jsonl)
```

## 📦 Logical Packages & Submodules

### 1. `fwber-backend` (Core API)
*   **Version:** Inherits Global `1.0.75`
*   **Role:** The brain. Handles all user data, authentication, Match/Proximity algorithms, and ActivityPub federation (inbox/outbox).
*   **Key Dependencies:**
    *   Laravel 12
    *   Stripe SDK (Payments/Subscriptions)
    *   Pusher/Laravel Reverb (WebSockets for chat/proximity)

### 2. `fwber-frontend` (Web UI)
*   **Version:** Inherits Global `1.0.75`
*   **Role:** The face. Consumes the backend API.
*   **Key Dependencies:**
    *   Next.js 16 (App Router)
    *   Framer Motion (Animations)
    *   TailwindCSS (Styling)

### 3. `fwber-geo` (Spatial Engine)
*   **Version:** 1.0.0 (Internal)
*   **Role:** High-performance spatial querying (finding users within X meters). Written in Rust for speed.
*   **Status:** Needs active integration checks with `fwber-backend`.

### 4. External Git Submodules
*   **Status:** No active external Git submodules configured in `.gitmodules`. All packages are currently tracked within the primary monorepo tree.
*   **Action Required:** If any external projects (e.g., specific ActivityPub forks, custom Rust crates) are referenced heavily, they should be added to the `submodules/` directory via `git submodule add`.

## 🔄 Sync Status
- Upstream forks and main branches were successfully merged.
- Next sync target: Implement deep E2E testing for the newly federated `ActivityPubKeyService`.