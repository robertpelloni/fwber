# Project Memory: fwber Architecture & Decisions

## High-Level Vision & Architecture
* **fwber** is a "privacy-first, proximity-based social operating system" aiming to replace swipe-based dating apps with real-world interactions.
* **Core Philosophy**: Zero-knowledge identity, AI-generated avatars before mutual matches, "Local Pulse" feeds, and strong location privacy.
* **Technology Stack**:
  * **Backend**: Node.js (ESM), Express.js, TypeScript. Recently migrated from Laravel PHP in Phase 5/6 (v2.0.0-ts).
  * **Database**: PostgreSQL or MySQL, using Prisma ORM.
  * **Real-time Engine**: Socket.io handles live presence (e.g., event maps). Replaced a Laravel Reverb/Pusher setup.
  * **Frontend**: Next.js 15 (React 19) utilizing Server Components, TailwindCSS, and PWA capabilities.
  * **Microservices**: A Rust geo-screener service handles high-density H3 spatial indexing (`fwber-geo`).
  * **Federation**: Moving towards ActivityPub interop, enabling users to interact over the Fediverse (via `/.well-known/webfinger` and Actor/Inbox routes).

## Major Subsystems & Features
1. **Core Loop**: Registration, ZK-identity, Onboarding, Discovery (Local Pulse), Matches.
2. **Federation (In Progress)**:
   * Prisma schema updated with `federation_follows`, `federation_inbox`, and `federation_outbox` models.
   * `users` table requires `public_key` and `private_key` fields for cryptographic payload signing/verification.
   * Endpoints managed under `fwber-backend-ts/src/routes/federation.ts`.
3. **Monetization & Economy**: Merchant marketplace, Stripe subscriptions, FWB loyalty token system, and Solana wallet hooks.
4. **Real-time Maps**: Frontend (`EventMap.tsx` using `react-leaflet`) hooks into Socket.io `location_updated` to visualize live coordinates.

## Operational & Deployment Patterns
* **Target Topology**: Vercel for Frontend, Hetzner VPS for Backend/DB/Websockets. DreamHost was explicitly retired.
* **Versioning**: A strict versioning protocol requires bumping `VERSION` (plain text) and updating `CHANGELOG.md` upon every significant change.
* **Database Management**: Prisma migrations (`npx prisma migrate dev`) are standard.
* **Testing**:
  * Backend testing relies on Jest configured for ESM (`node --experimental-vm-modules node_modules/jest/bin/jest.js`).
  * Local DB tests (e.g., `auth.test.ts`) require a functional mock or a live `DATABASE_URL` (like `mysql://root@localhost/fwber`) to pass successfully. ESM mocking with Prisma requires complex lifecycle handling or bypassing.
* **Clean Code**: Adherence to removing intermediate operational scripts (`patch_*.js`) before committing. Secrets must live in `.env` and be mocked in `.env.example`.

## Agent Directives
* Proceed autonomously through cycles without stopping, referencing `docs/UNIVERSAL_LLM_INSTRUCTIONS.md` and maintaining robust `TODO.md` tracking.
* Log progress extremely meticulously in `HANDOFF.md` to relay context across sessions.
* Refactor only to remove redundancy; do not mutate functional features without cause. Add code comments exclusively for "why," non-obvious algorithms, and trade-offs.
