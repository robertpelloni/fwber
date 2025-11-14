# Legacy Code Archive

This directory contains archived legacy code that has been replaced by modern implementations.

## Directory Structure

### `legacy-php/`
Contains all underscore-prefixed PHP files (`_*.php`) from the root directory. These files represent the original procedural PHP implementation of the dating platform before migration to Laravel backend.

**Status**: Archived - No longer served in production
**Reason**: Security hardening and migration to Laravel 12 backend
**Date Archived**: 2025-01-21

**Key Legacy Files**:
- `_makeAccount.php` - Original signup handler (replaced by Laravel API)
- `_getMatches.php` / `_getMatches_secure.php` - Matching logic (replaced by EnhancedMatchingEngine.php)
- `_profileVars.php` - Profile management (replaced by Laravel controllers)
- `_imageUpload.php` / `_getImage.php` - Image handling (replaced by Laravel storage)
- `_db.php` / `_init.php` - Database initialization (replaced by Laravel's Eloquent ORM)

**Security Notes**:
- Legacy files used mysqli with manual SQL escaping
- Some files contained SQL injection vulnerabilities
- Hardcoded secrets in `_secrets.php`
- Mixed MD5 and Argon2ID password hashing

## Do Not Deploy

Files in this archive should **never** be deployed to production servers. They are retained for:
- Historical reference
- Migration verification
- Understanding legacy business logic

## Modern Implementations

All functionality has been reimplemented in:
- **Backend**: `fwber-backend/` (Laravel 12 with PHP 8.3)
- **Frontend**: `fwber-frontend/` (Next.js 14 with TypeScript)

See main project README.md for deployment instructions.
