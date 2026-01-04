# Fwber

[![Agents & Docs](https://img.shields.io/badge/Agents-Docs-blue)](AGENTS.md)
[![Status: Live](https://img.shields.io/badge/Status-Live-green)](PROJECT_STATUS.md)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP 8.4+](https://img.shields.io/badge/PHP-8.4+-777BB4?logo=php)](https://www.php.net/)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel)](https://laravel.com/)
[![Next.js 14](https://img.shields.io/badge/Next.js-14-000000?logo=next.js)](https://nextjs.org/)

**fwber** is an open-source, privacy-first proximity dating platform that combines AI-generated avatars with location-based discovery. Built with modern technologies and a focus on user safety, fwber reimagines the casual dating experience.

## 🟢 Project Status
**Current Status: Post-Launch Monitoring & Growth**
See [PROJECT_STATUS.md](PROJECT_STATUS.md) for the detailed breakdown of completed features and the active roadmap.

## Table of Contents
- [Features](#features)
- [Quick Start](#quick-start)
- [Architecture](#architecture)
- [Development](#development)
- [Testing](#testing)
- [Deployment](#deployment)
- [Contributing](#contributing)
- [Roadmap](#roadmap)
- [License](#license)
The project is organized as a monorepo with the backend and frontend in separate directories:

-   `fwber-backend`: The Laravel (PHP) backend API.
-   `fwber-frontend`: The Next.js (React) frontend application.

## Getting Started

### 🎭 Avatar Mode
AI-generated avatars replace traditional profile photos:
- Eliminates appearance-based discrimination
- Discourages catfishing and fake profiles
- Levels the playing field for all users
- Configurable via `AVATAR_MODE` environment variable

### 📍 Local Pulse
Proximity-based discovery combining artifacts and matches:
- **Proximity Artifacts:** Location-tagged posts that auto-expire
  - Chat invitations
  - Board posts (discussion topics)
  - Announcements (events, meetups)
- **Match Candidates:** Nearby compatible users
- **Geo-Privacy:** Approximate distances only, never exact locations
- **Real-Time Updates:** Auto-refresh every 60 seconds

### 🔒 Privacy & Security
Privacy is built into every layer:
- Location fuzzing protects exact coordinates
- Avatar-only profiles (when enabled)
- Content sanitization on all user input
- HTTPS/TLS encryption in transit
- Bcrypt password hashing
- CSRF protection on all forms
- Rate limiting on authentication endpoints

### 🛡️ Safety & Moderation
Multi-layered safety features:
- User-initiated content flagging
- Automated TTL expiry for temporary content
- Shadow throttling for repeat offenders
- Geo-spoof detection (Phase 2)
- Transparent moderation policies

### 💬 Messaging
Off-platform communication:
- Share contact info only with authorized matches
- Use your preferred tools (email, Signal, Telegram, etc.)
- No in-app messenger = better privacy, less liability
- End-to-end encryption via third-party apps

### ♾️ Inclusive & Diverse
Support for all identities and preferences:
- All genders and orientations welcome
- Customizable preference matching
- Extensible interest/fetish system
- Community-driven feature additions

### 🤖 AI & Advanced Systems (New!)
- **AI Content Generation:** Profile bios, conversation starters, and post suggestions.
- **Recommendations:** AI-powered and location-based matching.
- **Proximity Chatrooms:** Real-time, location-gated group chats.

---

## Quick Start

### Prerequisites

*   **Backend:** PHP 8.4+, Composer 2.x, and either SQLite or MySQL.
*   **Frontend:** Node.js 18+ and npm.
*   **Containerized:** Docker and Docker Compose (recommended).

### Docker (Recommended)

1.  **Start Services:**
    ```bash
    sudo /usr/libexec/docker/cli-plugins/docker-compose -f docker-compose.dev.yml up -d
    ```

2.  **Run Migrations:**
    ```bash
    sudo /usr/libexec/docker/cli-plugins/docker-compose -f docker-compose.dev.yml exec laravel php artisan migrate
    ```

The frontend will be available at `http://localhost:3000` and the backend API at `http://localhost:8000`.

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for detailed guidelines. For information on working with AI agents on this repository, please see [AGENTS.md](AGENTS.md).
# Set up database
php artisan migrate
php artisan db:seed

# Run development server
php artisan serve
# API available at http://localhost:8000
```

### Frontend Setup

```bash
cd fwber-frontend

# Install dependencies
npm install

# Configure environment
cp .env.example .env.local
# Edit .env.local and set NEXT_PUBLIC_API_URL=http://localhost:8000/api

# Run development server
npm run dev
# App available at http://localhost:3000
```

### Docker Setup (Optional)

```bash
# Start all services
docker-compose up -d

# Run migrations
docker-compose exec backend php artisan migrate

# View logs
docker-compose logs -f
```

---

## Architecture

### Backend (Laravel 12)
```
fwber-backend/
├── app/
│   ├── Http/Controllers/
│   │   ├── Auth/              # Authentication
│   │   ├── Profile/           # User profiles
│   │   └── Proximity/         # Location features
│   ├── Models/
│   │   ├── User.php
│   │   ├── ProximityArtifact.php
│   │   └── UserPreference.php
│   ├── Services/
│   │   ├── MatchingService.php
│   │   ├── GeolocationService.php
│   │   └── AvatarService.php
│   └── ...
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php              # RESTful API routes
└── tests/
    ├── Feature/             # API integration tests
    └── Unit/                # Business logic tests
```

### Frontend (Next.js 14 + TypeScript)
```
fwber-frontend/
├── app/                     # App Router
│   ├── local-pulse/        # Local Pulse page
│   ├── profile/            # Profile management
│   └── matches/            # Match discovery
├── components/
│   ├── LocalPulse.tsx      # Main proximity feed
│   ├── AvatarCard.tsx      # User avatar display
│   └── ...
├── lib/
│   ├── api/                # API clients (Axios)
│   ├── hooks/              # React Query hooks
│   └── utils/
├── types/                  # TypeScript definitions
└── public/
```

### Technology Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | Laravel 12, PHP 8.4 |
| **Frontend** | Next.js 14, React 18, TypeScript 5.3 |
| **Database** | MySQL 8.0+ / SQLite (dev) |
| **Caching** | Redis (planned) |
| **Data Fetching** | React Query (@tanstack/react-query) |
| **HTTP Client** | Axios |
| **Styling** | Tailwind CSS 3.4 |
| **Icons** | Lucide React |
| **UI Components** | Radix UI |
| **State** | Zustand, React Query |
| **Testing** | PHPUnit (backend), Jest (frontend planned) |

### API Endpoints

**Authentication:**
- `POST /api/register` - User registration
- `POST /api/login` - User login
- `POST /api/logout` - User logout

**Profiles:**
- `GET /api/profile` - Get authenticated user profile
- `PUT /api/profile` - Update profile
- `POST /api/profile/avatar/generate` - Generate AI avatar

**Proximity:**
- `GET /api/proximity/local-pulse` - Get merged feed (artifacts + candidates)
- `GET /api/proximity/artifacts` - List proximity artifacts
- `POST /api/proximity/artifacts` - Create artifact
- `GET /api/proximity/artifacts/{id}` - Get single artifact
- `POST /api/proximity/artifacts/{id}/flag` - Flag artifact
- `DELETE /api/proximity/artifacts/{id}` - Delete artifact

**Matching:**
- `GET /api/matches` - Get match list
- `POST /api/matches/{id}/authorize` - Authorize match

---

## Development

### Agents & development workflow

To keep AI assistants aligned and shipping high-quality code, see:

- **Master Protocol**: [`AGENTS.md`](AGENTS.md)
- **Copilot Instructions**: [`copilot-instructions.md`](copilot-instructions.md)
- **Claude Guidelines**: [`CLAUDE.md`](CLAUDE.md)
- **Feature Flags**: [`docs/FEATURE_FLAGS.md`](docs/FEATURE_FLAGS.md)

Key principles:
- **Versioning**: Every build must have a new version number.
- **Feature Flags**: Keep non-MVP features behind flags.
- **Testing**: Validate routes and run E2E tests.

### Running Tests

**Backend:**
```bash
cd fwber-backend

# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/ProximityArtifactTest.php

# Run specific test method
php artisan test --filter test_proximity_artifacts_expire_after_ttl
```

**Current Test Coverage:**
- ✅ 131 tests passing
- ✅ 524 assertions
- ✅ Zero regressions

**Frontend:**
```bash
cd fwber-frontend

# Run tests (when available)
npm test

# Type checking
npm run type-check

# Linting
npm run lint
```

### Code Quality

**Backend:**
```bash
# Static analysis
./vendor/bin/phpstan analyse

# Code formatting
./vendor/bin/pint

# Fix formatting
./vendor/bin/pint --repair
```

**Frontend:**
```bash
# ESLint
npm run lint

# Type checking
npm run type-check

# Prettier (if configured)
npm run format
```

### Environment Variables

**Backend (`.env`):**
```env
APP_NAME=fwber
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
# Or for MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=fwber
# DB_USERNAME=root
# DB_PASSWORD=

AVATAR_MODE=true
REPLICATE_API_TOKEN=your_token_here
OPENAI_API_KEY=your_key_here
```

**Frontend (`.env.local`):**
```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_AVATAR_MODE=true
```

---

## Testing

### Phase 1 Test Coverage

**Authentication & Profiles:**
- ✅ User registration with validation
- ✅ Login/logout flows
- ✅ Profile CRUD operations
- ✅ Avatar generation and enforcement
- ✅ Avatar-only mode restrictions

**Proximity Features:**
- ✅ Artifact creation (chat/board_post/announce)
- ✅ TTL expiry mechanics
- ✅ Geolocation filtering
- ✅ Radius-based queries
- ✅ Local Pulse merged endpoint
- ✅ Content sanitization
- ✅ Flag escalation

**Security:**
- ✅ CSRF protection
- ✅ Rate limiting
- ✅ Input validation
- ✅ Authorization checks
- ✅ SQL injection prevention

---

## Deployment

### Production Checklist

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure production database (MySQL/PostgreSQL)
- [ ] Set up Redis for caching
- [ ] Configure HTTPS/SSL certificates
- [ ] Set secure session cookies
- [ ] Configure CORS policies
- [ ] Set up CDN for static assets
- [ ] Enable rate limiting
- [ ] Configure backup strategies
- [ ] Set up monitoring (Sentry, New Relic, etc.)
 - [ ] Do not expose internal services (MySQL/Redis) publicly
 - [ ] Ensure only Nginx is exposed on 80/443
 - [ ] Confirm no .env files are committed or deployed
- [ ] Review and update PRIVACY.md and TERMS.md
- [ ] Configure email service (SMTP, SendGrid, etc.)

### Deployment Options

**Traditional Hosting:**
```bash
# Build frontend
cd fwber-frontend
npm run build

# Deploy backend with Laravel Forge, Ploi, or manual setup
# Configure nginx/Apache for Laravel
# Set up supervisor for queue workers
```

**Docker (One-Command Setup):**
```bash
# Initialize complete production environment
./init-production.sh
```

This script handles:
- Pre-flight checks (Docker, environment files)
- Building Docker images
- Starting all services
- Running migrations
- Optimizing Laravel caches
- Setting up storage symlinks

**Docker (Manual Setup):**
```bash
# Build production images
docker compose -f docker-compose.prod.yml build

# Start stack (nginx is the only public entrypoint)
docker compose -f docker-compose.prod.yml up -d

# Run backend migrations
docker compose -f docker-compose.prod.yml exec laravel php artisan migrate --force

# Optimize Laravel
docker compose -f docker-compose.prod.yml exec laravel php artisan config:cache
docker compose -f docker-compose.prod.yml exec laravel php artisan route:cache
docker compose -f docker-compose.prod.yml exec laravel php artisan view:cache

# Set up storage
docker compose -f docker-compose.prod.yml exec laravel php artisan storage:link

# Tail logs
docker compose -f docker-compose.prod.yml logs -f --tail=100
```

**Serverless:**
- Laravel Vapor (AWS Lambda)
- Vercel (frontend)
- PlanetScale (database)

---

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for detailed guidelines.

**Quick Contribution Guide:**

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes with tests
4. Ensure all tests pass (`php artisan test`)
5. Commit with clear messages (`git commit -m 'feat: Add amazing feature'`)
6. Push to your fork (`git push origin feature/amazing-feature`)
7. Open a Pull Request

**Areas We Need Help:**
- 🎨 UI/UX design and frontend components
- 🧪 Test coverage expansion
- 📚 Documentation improvements
- 🌍 Internationalization (i18n)
- ♿ Accessibility enhancements
- 🐛 Bug fixes and performance optimizations

---

## Roadmap

**Current Status:** v1.0.0-rc1 (Release Candidate)

We follow [Semantic Versioning](https://semver.org/).

- **v1.0.0 (Current):** MVP + Hardening
  - ✅ **Phase 1:** Core Features (Avatar Mode, Proximity, Local Pulse)
  - ✅ **Phase 2:** Safety & Production Readiness (Shadow throttling, Geo-spoof detection, SSL, Monitoring)

- **Next Steps (Post-MVP):**
  - 🚀 **Phase 3:** [Scale, Security & Observability](docs/roadmap/PHASE_2_SCALE_AND_SECURITY.md) (Redis, Queues, SSO, OpenTelemetry)
  - 🎨 **Phase 4:** UX Polish & Mobile App (React Native)
  - 🌍 **Phase 5:** Community & Growth

See [docs/roadmap/](docs/roadmap/) for detailed plans.

---

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

**Previous versions used AGPL v3. The modern rewrite is MIT-licensed for maximum adoption.**

---

## Legacy Documentation

### fwber.me Modernization Project

This document outlines the historical state of the fwber.me application following a comprehensive security and architectural overhaul from the legacy 2011 PHP codebase.

#### Legacy Overview

The original fwber.me application was a legacy PHP project from 2011. The primary goal of the modernization effort was to address critical security vulnerabilities and refactor the core application to use modern, professional-grade development practices.

#### Legacy Technology Stack
- **Technology Stack:** PHP 8+, PDO for database access, Composer for dependency management.
- **Key Libraries:** `phpmailer/phpmailer`, `vlucas/phpdotenv`.
- **Modernized Pages:** `index.php`, `signin.php`, `signout.php`, `forgot-password.php`, `settings.php`, `profile.php`, `edit-profile.php`, `matches.php`, `manage-pics.php`, `contact.php`.

#### Legacy Architectural Components

The legacy architecture was designed to be modular and secure, with a clear separation of concerns.

**`_init.php`** - Central bootstrap file:
1. Loading the Composer autoloader
2. Loading environment variables from `.env` 
3. Establishing secure PDO database connection
4. Initializing core manager classes

**Manager Classes:**
- **`SecurityManager.php`**: Password hashing (Argon2ID), session management, CSRF protection, rate limiting
- **`ProfileManager.php`**: User profile data management for `users` and `user_preferences` tables
- **`PhotoManager.php`**: Secure file uploads with MIME validation, deletion, database integration

**Security Improvements:**
- ✅ CSRF protection on all forms
- ✅ Rate limiting on auth endpoints
- ✅ HTTPS enforcement
- ✅ Argon2ID password hashing (replaced MD5)
- ✅ PDO prepared statements (SQL injection prevention)

**Note:** The legacy PHP codebase has been superseded by the modern Laravel 11 backend. See above for current architecture.

#### Legacy Migration & Diagnostics

The legacy codebase included migration scripts for database compatibility. These are preserved for historical reference but are not needed for the modern Laravel implementation.

<details>
<summary>Legacy Migration Instructions (archived)</summary>

**Enable debug and start the app:**
```cmd
copy .env.example .env
notepad .env
```
Set `DEBUG_MODE=true`, then:
```cmd
php -S 127.0.0.1:8000
```

**Apply migration:**
- Visit: `http://127.0.0.1:8000/scripts/apply_migration_web.php`
- Expected: `Applied N migration statements successfully.`

**Verify columns:**
- Visit: `http://127.0.0.1:8000/scripts/profile_diagnostics.php`

**CLI migration:**
```cmd
php scripts\apply_migration.php
# Or:
mysql -h localhost -u fwber -p fwber < db\migrations\2025-10-11-legacy-matcher-compat.sql
```

</details>

---

## Community & Support

- 📖 **Documentation:** [docs/](docs/)
- 🐛 **Bug Reports:** [GitHub Issues](https://github.com/yourusername/fwber/issues)
- 💬 **Discussions:** [GitHub Discussions](https://github.com/yourusername/fwber/discussions)
- 📧 **Contact:** [support@fwber.me](mailto:support@fwber.me)
- 🌐 **Website:** [fwber.me](https://fwber.me)

---

## Acknowledgments

This project was modernized through collaboration between human developers and AI assistants (Gemini and Claude). The development process was iterative and conversational, reflecting the evolution of modern software development practices.

**Special Thanks:**
- All contributors and testers
- The Laravel and Next.js communities
- Open source projects that made this possible

---

**Built with ❤️ for privacy, safety, and genuine human connection.**
For instructions on how to set up and run the development environments for the backend and frontend, please refer to the `README.md` files within each directory.
