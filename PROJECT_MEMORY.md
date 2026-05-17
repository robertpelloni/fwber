# PROJECT_MEMORY

## Architecture Context
The project `fwber` is a sophisticated full-stack monorepo consisting of:
*   **Frontend:** A modern Next.js React application (`fwber-frontend`), utilizing features like WebAssembly (`lib/wasm`) for high-performance tasks, real-time WebSocket hooks (`use-websocket.ts`, `use-socket-logic.ts`), Radix UI for tooltips and standard components, and a comprehensive routing structure utilizing the App Router.
*   **Backend:** An Express/TypeScript backend (`fwber-backend-ts`), heavily reliant on Prisma for ORM and database management. It supports a vast array of features including ActivityPub Federation, crypto payments/wallets, achievements, AI moderation, proximity chatrooms, content generation, and real-time proximity chat via WebSockets (`socket.ts`).
*   **Infrastructure:** A robust deployment stack designed for Kubernetes (`docker/k8s/`), employing Redis for high-performance state management (e.g., verification stores, caching), and integrating with Datadog for APM monitoring, and Sentry for error tracking.

## Core Directives & Project Vision
*   **Radical Privacy & Authentic Identity:** The project's north star, outlined in `docs/VISION.md`, emphasizes a shift from a standard dating app to a "Local Social Economy".
*   **Universal AI Protocol:** All AI interactions and code generation must adhere strictly to `docs/universal/UNIVERSAL_LLM_INSTRUCTIONS.md`, ensuring all implemented features are 100% represented in the UI, fully documented with tooltips, and securely merged without regressions.

## Recent Significant Patches
*   **ActivityPub Federation:** Critical SSRF vulnerabilities were identified and patched within `FederationService.ts`.
*   **State Management Migration:** Verification states were successfully migrated from local memory to a scalable Redis store (`verification-store.ts`), resolving critical Event Loop blocking issues that plagued backend performance under load.
*   **Git Environment Stabilization:** The workspace was crippled by tracked build artifacts. A severe and aggressive `.gitignore` policy was enforced, followed by a hard reset and cache purge, restoring the ability of AI agents to diff and analyze code efficiently.

## Development Patterns
*   **Feature Flags:** extensively used in the frontend (`fwber-frontend/lib/hooks/use-feature-flags.ts`) and backend to toggle advanced capabilities like `video_chat`, `ai_wingman`, and `federation`.
*   **AI Integration:** The backend heavily utilizes a Driver pattern for services, notably the `MediaAnalysisServiceProvider` which seamlessly switches between OpenAI, AWS, and mock drivers for content moderation and the "AiWingman" features.
*   **Caching & Performance:** High-traffic read paths (Profiles, Analytics, Feeds) aggressively utilize Redis caching to prevent database bottlenecking.

## Current Technical Blockers
*   **Sandbox Isolation:** The current execution environment prohibits interactive remote git operations (`push`, `pull`). All merges must be non-interactive, and final deployment/pushes rely on the host user. The git environment is also currently exhibiting a persistent "diff size unusually large" warning despite `.gitignore` updates, which is impeding automated diff analysis.

## Gap Analysis (New Findings)
*   **Missing UI Implementations:** Cross-referencing backend API routes (`fwber-backend-ts/src/routes`) against frontend application pages (`fwber-frontend/app`) revealed several gaps. Specific backend routes like `bulletin-boards`, `referrals`, `bounties`, `leaderboard`, `reports`, `burner-links`, and `content-generation` lacked complete UI representations.
*   **Resolution Progress:** I have successfully designed and implemented the missing `content-generation` UI (`fwber-frontend/app/content-generation/page.tsx`), providing a user interface for AI-powered dating profile bio generation using the pre-existing backend endpoint.
