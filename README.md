# fwber.me

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Status: Beta](https://img.shields.io/badge/Status-Beta-yellow)](PROJECT_STATUS.md)
[![PHP 8.4+](https://img.shields.io/badge/PHP-8.4+-777BB4?logo=php)](https://www.php.net/)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel)](https://laravel.com/)
[![Next.js 16](https://img.shields.io/badge/Next.js-16-000000?logo=next.js)](https://nextjs.org/)

Privacy-first proximity dating platform. Open source. Work in progress.

**Status:** Beta (v0.5.0) — functional but seeking early testers in Detroit metro.  
**Stack:** Laravel 12 (PHP 8.4) + Next.js 16 (React 18) + MySQL  
**License:** MIT

## What This Is

A dating app where AI avatars replace photos until you choose to reveal yourself. Location is fuzzed. Messages can be encrypted. Your data stays yours.

### Core Features
- **AI Avatar Mode** — AI-generated avatars until mutual reveal
- **Local Pulse** — Proximity-based discovery feed (artifacts + match candidates)
- **5-Tier Relationship Reveal** — Progressive trust-building (Discovery → Verified)
- **Privacy by Design** — Location fuzzing, ghost mode, data export

### Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12, PHP 8.4, MySQL |
| Frontend | Next.js 16, React 18, TypeScript |
| Real-time | Laravel Reverb (WebSocket) |
| Styling | Tailwind CSS |
| State | Zustand, React Query |

## Quick Start

### Prerequisites
- **Backend:** PHP 8.4+, Composer 2.x, MySQL or SQLite
- **Frontend:** Node.js 20+, npm

### Backend
```bash
cd fwber-backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
# API at http://localhost:8000
```

### Frontend
```bash
cd fwber-frontend
npm install
cp .env.example .env.local
npm run dev
# App at http://localhost:3000
```

### Docker
```bash
docker compose -f docker-compose.dev.yml up -d
docker compose -f docker-compose.dev.yml exec laravel php artisan migrate
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

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines. This project uses AI agents (Claude, Gemini, GPT) for development — see [AGENTS.md](AGENTS.md).

## License

MIT License — see [LICENSE](LICENSE).

Previous versions of the legacy PHP codebase (2011) used AGPL-v3. The modern rewrite is MIT-licensed.

---

**Built for privacy, safety, and genuine human connection.**
