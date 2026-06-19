Here is a comprehensive summary of the project's architecture, patterns, and decisions based on my exploration and work within the codebase:

### 1. High-Level Vision & Core Philosophy
*   **Identity & Discovery**: `fwber` is a privacy-first, proximity-based social operating system. It eschews traditional swipe-based dating mechanics in favor of "Local Pulse" feeds, ZK-identity (zero-knowledge verification), and AI-generated avatars that mask real photos until mutual trust is established.
*   **Evolution**: The project's vision (`docs/VISION.md`) has been shifting from a pure proximity dating app to a broader "Local Social Economy," incorporating merchants, community boards, and local quests.

### 2. Technology Stack & Architecture
*   **Backend (`fwber-backend-ts`)**: A Node.js (ESM) + Express.js backend written in TypeScript. It recently underwent a massive "Great Migration" (v2.0.0-ts) from a legacy Laravel PHP architecture.
*   **Database**: Managed via Prisma ORM. Compatible with MySQL (production) and PostgreSQL.
*   **Frontend (`fwber-frontend`)**: Built with Next.js 16 (App Router), React 18, Tailwind CSS, and Radix UI primitives. It supports PWA capabilities.
*   **Real-time Capabilities**: Replaced legacy Laravel Reverb/Pusher with `socket.io` (backend) and `socket.io-client` (frontend) for chat, typing indicators, and live event map location broadcasting (`react-leaflet`).
*   **Microservices (`fwber-geo`)**: High-density spatial indexing and proximity filtering are offloaded to a custom Rust microservice utilizing H3-js (hierarchical hexagonal indexing) and Redis Bloom Filters.

### 3. Major Subsystems & Domain Logic
*   **Federation Engine**: The platform acts as a Fediverse node using ActivityPub. It supports WebFinger, Inbox/Outbox handling, and cross-instance follows.
    *   **Security**: Uses 2048-bit RSA keypairs (generated async via `CryptoService`) and implements strict SSRF mitigation (enforcing HTTPS, blocking internal IPs like `10.*` and `192.168.*`) in `FederationService`.
*   **AI Integration & Intelligence**: Driven by `LlmManager` with multi-provider failover (OpenRouter, OpenAI, NVIDIA NIM). AI capabilities include:
    *   `AiWingmanService`: Proactive nudges, Tone Translation (flirty, confident, etc.), and Profile Roasts.
    *   `SentimentAnalysisService`: Computes single-user emotion, neighborhood vibe, `calculateConversationVibe` for 1-on-1 matches, and the newly added `calculateGroupAura` for dynamic chatroom styling.
    *   `ContentModerationService`: Uses LLM checks with a strict local regex fallback for immediate protection if AI is unavailable.
*   **Economy & Monetization**: Includes FWB token rewards (e.g., for quests or referrals), Stripe integration for subscriptions, and Solana wallet hooks. *Note: Stripe npm package is required for TypeScript compilation in Hetzner CI/CD.*
*   **Matching Engine**: OkCupid-style geometric-mean compatibility heuristic based on user-weighted values, overlaid with proximity history (80/20 weighting) to generate "Narrative Compatibility" reports.

### 4. Database & ORM Patterns
*   **`BigInt` Heavy**: The `users` table utilizes `BigInt` for the primary key `id`. All relational models (like `user_integrations`, `messages`, `matches`) must use `BigInt` foreign keys. This requires careful parsing in frontend/JSON boundaries (handled via a custom `serialize` utility).
*   **Migrations**: The repo explicitly does not track Prisma migration files (`prisma/migrations`) locally. Live deployments rely on `npx prisma db push` or `npx prisma migrate deploy`.
*   **Enums**: Former Prisma Enums (like `UserRole` and `Tier`) have been replaced with direct string values in controllers due to schema changes. Complex queries (like journal entry counts) sometimes resort to `$queryRaw`.

### 5. Testing & Verification
*   **Backend**: Uses Jest + Supertest. Because it's an ESM project, Jest must run with `--experimental-vm-modules`. Prisma connections are mocked using `jest.unstable_mockModule` to avoid eager DB connections. Some local tests (e.g., `auth.test.ts`) are known to hang.
*   **Frontend & E2E**: Verified using `cd fwber-frontend && npm run lint` and `npm run build`. Playwright/Python scripts reside in `fwber-frontend/verification/` for UI snapshots and data compliance checks.

### 6. Operational & AI Agent Guidelines
*   **Master Protocol**: All agents must strictly adhere to `docs/UNIVERSAL_LLM_INSTRUCTIONS.md`.
*   **Continuous Execution**: AI agents are instructed to run continuously and autonomously, selecting features from `TODO.md` and logging extreme detail in `HANDOFF.md` between sessions.
*   **Git & Versioning**: Every completed feature demands a strict version bump in the root `VERSION` file, `VERSION.md`, `CHANGELOG.md`, `ROADMAP.md`, and `TODO.md`. The commit message must include this version.
*   **Sanitization Rules**: The `.gitignore` aggressively excludes build artifacts (`dist`, `.next`, `node_modules`) to avoid massive Git diffs that crash AI context windows. AI agents must intelligently merge local feature branches into `main` before starting new work.
*   **Agent Safety**: Never execute commands that kill all node processes generically (e.g., `killall node`), as this will kill the AI agent's own execution session.