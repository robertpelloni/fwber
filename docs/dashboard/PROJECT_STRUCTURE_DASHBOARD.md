# Project Structure Dashboard

**Generated:** February 20, 2026
**Version:** v0.3.36

## Directory Layout

```
.
├── VERSION                     # Global version file
├── docs/                       # Project documentation
│   ├── dashboard/              # Dashboards & Reports
│   ├── universal/              # Universal LLM Instructions
│   ├── perf/                   # Performance Reports
│   └── testing/                # Test Plans
├── fwber-backend/              # Laravel API
│   ├── app/                    # Core Application Logic
│   ├── config/                 # Configuration Files
│   ├── database/               # Migrations & Seeds
│   ├── routes/                 # API Routes
│   └── tests/                  # PHPUnit Tests
├── fwber-frontend/             # Next.js Application
│   ├── app/                    # App Router Pages
│   ├── components/             # React Components
│   ├── lib/                    # Utilities & Hooks
│   └── verification/           # Playwright Scripts
└── docker/                     # Infrastructure
    └── k8s/                    # Kubernetes Manifests
```

## Submodules / Packages

| Module | Type | Version | Path |
|--------|------|---------|------|
| **Backend** | Laravel | 12.x | `fwber-backend/` |
| **Frontend** | Next.js | 16.1.1 | `fwber-frontend/` |
| **Database** | MySQL | 8.0 | `docker-compose.yml` |
| **Cache** | Redis | Alpine | `docker-compose.yml` |
