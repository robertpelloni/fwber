# Legacy PHP Application Files

This directory contains the original flat PHP application that preceded the Laravel backend migration.

## Status

**⚠️ DEPRECATED** - These files are preserved for reference only and should not be used in active development.

## Migration Context

The FWBER application has been migrated to a modern Laravel 11 backend (see `../fwber-backend/`). This migration provides:

- **Modern architecture**: MVC pattern, dependency injection, service containers
- **Security**: JWT authentication, rate limiting, CSRF protection
- **API-first design**: RESTful endpoints with OpenAPI documentation
- **Advanced features**: Real-time messaging (Mercure/WebSocket), AI integrations, content moderation
- **Testing**: PHPUnit test suites, API test coverage
- **Developer experience**: Artisan CLI, migrations, seeders, factories

## What's in this directory

### Authentication & Authorization
- `admin-dashboard.php`, `admin-login.php` - Admin interface (replaced by Laravel admin routes)
- `signin.php`, `signout.php` - User authentication (replaced by JWT auth API)
- `forgot-password.php`, `forgotpassword.php` - Password reset flow
- `verify-email.php`, `verify.php` - Email verification

### User Management & Profiles
- `edit-profile.php`, `editprofile.php`, `profile.php`, `profile-form.php` - Profile management
- `settings.php` - User settings

### Core Features
- `matches.php`, `location-matches.php`, `realtime-matches.php` - Matching engine
- `manage-pics.php`, `managepics.php` - Photo management
- `realtime-demo.php` - Real-time feature demos

### Business Logic Classes
- `MatchingEngine.php`, `EnhancedMatchingEngine.php`, `ai-matching-engine.php` - Matching algorithms
- `PhotoManager.php` - Photo handling
- `ProfileManager.php` - Profile operations
- `avatar-generator.php` - Avatar generation
- `recaptchalib.php` - ReCAPTCHA integration

### Configuration & Security
- `config-template.php` - Configuration template
- `secure-config.php`, `security-manager.php` - Security utilities

### Venue Features
- `venue-dashboard.php`, `venue-login.php` - Venue management (feature-flagged in Laravel)

### Public Pages
- `index.php`, `index-mvp.php` - Landing pages
- `contact.php`, `privacy.php`, `tos.php` - Legal/info pages
- `join.php`, `unsubscribe.php` - User actions

### Database Setup
- `setup-admin-tables.sql` - Admin schema
- `setup-database.sql` - Main database schema
- `setup-venue-tables.sql` - Venue schema

### Utility Files
- `f.php`, `h.php`, `l.php`, `head.php` - Various utility scripts

## Laravel Equivalents

| Legacy File | Laravel Equivalent |
|-------------|-------------------|
| `signin.php` | `POST /api/auth/login` (AuthController) |
| `matches.php` | `GET /api/matches` (MatchController) |
| `profile.php` | `GET /api/profile` (ProfileController) |
| `admin-dashboard.php` | Admin routes under `/api/admin/*` |
| Database setup scripts | Laravel migrations in `fwber-backend/database/migrations/` |

## If You Need These Files

If you discover that legacy functionality is still actively used:

1. **Review** `docs/LEGACY_DEPRECATION_PLAN.md` for migration strategy
2. **Check** Laravel backend for equivalent implementation
3. **File an issue** if critical functionality is missing from Laravel backend
4. **Restore to root** only as a temporary measure (see revert instructions in `docs/CLEANUP_README.md`)

## Maintenance

- **Do not modify** files in this directory for new features
- **Do not depend** on these files in new code
- **Do consider** removing this directory entirely once migration is confirmed complete

## Questions?

See project documentation:
- Migration roadmap: `../docs/roadmap/`
- Feature flags: `../docs/FEATURE_FLAGS.md`
- Architecture decisions: `../docs/adr/`
