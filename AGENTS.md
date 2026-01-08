# FWBER KNOWLEDGE BASE

**Context:** Dating Platform (Laravel/Next.js)
**Parent:** [Workspace Root](../AGENTS.md)
**Master Protocol:** [docs/UNIVERSAL_LLM_INSTRUCTIONS.md](docs/UNIVERSAL_LLM_INSTRUCTIONS.md)

## OVERVIEW
Production-ready privacy-first dating platform. Monorepo combining Laravel 12 backend with Next.js 14 frontend.

## 🧭 NAVIGATION
| Domain | Location | Tech Stack |
|--------|----------|------------|
| **Frontend** | [fwber-frontend/AGENTS.md](fwber-frontend/AGENTS.md) | Next.js 14, React, Tailwind, Shadcn |
| **Backend** | [fwber-backend/AGENTS.md](fwber-backend/AGENTS.md) | Laravel 12, PHP 8.4, MySQL, Spatial |

## STRUCTURE
```
fwber/
├── fwber-backend/   # API & Core Logic
├── fwber-frontend/  # Web Application
├── docker/          # Container configs
└── docs/            # Project documentation
```

## CONVENTIONS
- **Versioning**: Increment `VERSION` on changes.
- **Testing**: `php artisan test` (Backend) & `npm test` (Frontend).
- **Docs**: Update `PROJECT_STATUS.md` and `CHANGELOG.md` faithfully.

## ANTI-PATTERNS
- **NO** "It works on my machine" - Run CI commands.
- **NO** committing without running tests.
- **NO** direct database access from frontend.

## 🚀 OPERATIONAL CHARTER
**Outstanding work. Absolutely phenomenal. Unbelievable. Simply Fantastic, extraordinary, marvelous. Mind-blowing. Magnificent. Please keep going, please continue, please proceed!**
