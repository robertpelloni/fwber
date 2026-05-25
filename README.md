# fwber.me

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Status: Beta](https://img.shields.io/badge/Status-Beta-yellow)](PROJECT_STATUS.md)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.0+-3178C6?logo=typescript)](https://www.typescriptlang.org/)
[![Node.js](https://img.shields.io/badge/Node.js-20+-339933?logo=node.js)](https://nodejs.org/)
[![Next.js 15](https://img.shields.io/badge/Next.js-15-000000?logo=next.js)](https://nextjs.org/)
[![Prisma](https://img.shields.io/badge/Prisma-6.0+-2D3748?logo=prisma)](https://www.prisma.io/)

Privacy-first proximity dating platform. Open source. Unified TypeScript stack.

**Status:** Beta (v2.0.0-ts) — Full TypeScript/Node.js Migration Complete.  
**Stack:** Node.js (Express) + Next.js 15 (React 19) + Prisma (PostgreSQL/MySQL)  
**License:** MIT

## What This Is

A dating app where AI avatars replace photos until you choose to reveal yourself. Location is fuzzed. Messages are end-to-end encrypted. Your data stays yours.

### Core Features
- **AI Avatar Mode** — AI-generated avatars until mutual reveal
- **Local Pulse** — Proximity-based discovery feed (artifacts + match candidates)
- **5-Tier Relationship Reveal** — Progressive trust-building (Discovery → Verified)
- **Privacy by Design** — ZK-Identity, location fuzzing, ghost mode, E2E encryption

### Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Node.js, Express, TypeScript, Prisma |
| Frontend | Next.js 15, React 19, TypeScript |
| Real-time | Socket.io (WebSocket) |
| Database | PostgreSQL / MySQL |
| Styling | Tailwind CSS |
| State | Zustand, React Query |

## Quick Start

### Prerequisites
- **Node.js:** 20+ (LTS recommended)
- **npm / bun:** Package manager
- **Database:** PostgreSQL or MySQL

### Backend (Express + Prisma)
```bash
cd fwber-backend-ts
npm install
cp .env.example .env
npx prisma generate
npx prisma migrate dev
npm run dev
# API at http://localhost:4000
```

### Frontend (Next.js)
```bash
cd fwber-frontend
npm install
cp .env.example .env.local
npm run dev
# App at http://localhost:3000
```

### Docker
```bash
docker compose up -d
docker compose exec backend npx prisma migrate deploy
```

## Documentation

| Doc | Purpose |
|-----|---------|
| [PROJECT_STATUS.md](PROJECT_STATUS.md) | Current state |
| [CHANGELOG.md](CHANGELOG.md) | Version history |
| [docs/FEATURE_STATUS_MATRIX.md](docs/FEATURE_STATUS_MATRIX.md) | Feature maturity |
| [DEPLOY.md](DEPLOY.md) | Deployment instructions |
| [CONTRIBUTING.md](CONTRIBUTING.md) | Contribution guidelines |
| [SECURITY.md](SECURITY.md) | Security policy |
| [AGENTS.md](AGENTS.md) | AI agent protocols |

## Billing Launch Notes

Premium upgrades now require a real Stripe payment method or payment intent before Gold is granted. To take billing live, configure the backend Stripe secrets, add the frontend publishable key, set `PAYMENT_DRIVER=stripe`, and register the webhook endpoint described in [DEPLOY.md](DEPLOY.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines. This project uses AI agents (Claude, Gemini, GPT) for development — see [AGENTS.md](AGENTS.md).

## License

MIT License — see [LICENSE](LICENSE).

Previous versions of the legacy PHP codebase (2011) used AGPL-v3. The modern rewrite is MIT-licensed.

---

**Built for privacy, safety, and genuine human connection.**
