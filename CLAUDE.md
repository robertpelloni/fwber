# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

FWBer.me is an adult dating/matching platform with both legacy PHP and modern implementations:
- **Legacy PHP application** (root directory) - Traditional PHP with MySQL backend
- **Modern Laravel backend** (fwber-backend/) - Laravel 12 API
- **Modern Next.js frontend** (fwber-frontend/) - Next.js 15 with TypeScript

## Core Architecture

### Legacy PHP Application
- **Authentication**: Session-based using `_init.php:validateSessionOrCookiesReturnLoggedIn()`
- **Database**: Direct MySQL queries via mysqli
- **File Structure**: Flat PHP files with underscore prefixed utility files (`_*.php`)
- **URL Routing**: Apache `.htaccess` rewrite rules for clean URLs
- **Core Files**:
  - `_init.php` - Main initialization and authentication functions
  - `_getProfile.php` - User profile data retrieval
  - `_getMatches.php` - Matching algorithm implementation
  - `_profileVars.php` - Profile field definitions and validation

### Modern Laravel Backend (fwber-backend/)
- **Framework**: Laravel 12 with PHP 8.2+
- **Development Server**: `composer dev` (runs server, queue, logs, and vite concurrently)
- **Testing**: `composer test` (clears config and runs PHPUnit)
- **Build**: `npm run build` for frontend assets

### Modern Next.js Frontend (fwber-frontend/)
- **Framework**: Next.js 15 with App Router and TypeScript
- **Styling**: Tailwind CSS
- **Authentication**: NextAuth.js with Prisma adapter
- **Database**: Prisma ORM

## Common Development Commands

### Laravel Backend (fwber-backend/)
```bash
# Development (runs all services)
composer dev

# Individual services
php artisan serve
php artisan queue:listen --tries=1
php artisan pail --timeout=0
npm run dev

# Testing
composer test
# Or manually:
php artisan config:clear
php artisan test

# Database
php artisan migrate
php artisan db:seed

# Cache clearing
php artisan cache:clear
php artisan config:clear
```

### Next.js Frontend (fwber-frontend/)
```bash
# Development (with Turbopack)
npm run dev

# Build (with Turbopack)
npm run build

# Production
npm run start

# Linting
npm run lint
```

### Legacy PHP
- **Web Server**: Apache with mod_rewrite (requires XAMPP/LAMP)
- **Database**: MySQL accessed via `_secrets.php` configuration
- **No build process** - direct PHP execution

## Key Implementation Details

### User Matching Algorithm
The core matching logic considers:
- Gender preferences (`wantsPenis()`, `wantsBreasts()`, `wantsBodyHair()`)
- Location-based distance calculation (`getDistanceBetweenPoints()`)
- Sexual interests and fetishes (stored in profile variables)
- Avatar generation based on user attributes

### Authentication Flow
- **Legacy**: Cookie + session validation with salted password hashing
- **Modern**: JWT tokens via Laravel Sanctum + NextAuth.js

### Profile System
- Comprehensive profile attributes in `_profileVars.php`
- Automatic avatar generation from profile data
- Privacy controls for photo sharing
- Age verification and location services

## File Organization

### Legacy PHP Files
- `_*.php` - Utility/library functions
- `*.php` - Page controllers/views
- `styles.css` - Main stylesheet
- `js/` - JavaScript files
- `images/` - Static assets
- `avatars/` - Generated user avatars

### Modern Structure
- `fwber-backend/app/` - Laravel application logic
- `fwber-backend/routes/` - API route definitions  
- `fwber-frontend/app/` - Next.js app router pages
- `fwber-frontend/prisma/` - Database schema and migrations

## Environment Setup

### Prerequisites
- **Legacy**: Apache, MySQL, PHP 7.4+
- **Modern Backend**: PHP 8.2+, Composer, MySQL 8.0+
- **Modern Frontend**: Node.js 18+, npm

### Configuration Files
- `.env` (Laravel backend configuration)
- `.env.local` (Next.js frontend configuration)
- `_secrets.php` (Legacy PHP database credentials)

## Testing Strategy

- **Laravel**: PHPUnit tests via `composer test`
- **Next.js**: ESLint via `npm run lint`
- **Legacy**: Manual testing (no automated test suite)

## Development Notes

- The legacy PHP application uses direct SQL queries - be cautious with SQL injection
- Modern implementations use proper ORM/prepared statements
- Avatar generation requires GenAI API integration (configurable endpoints)
- Location features require GPS/geolocation API integration
- Email functionality depends on SMTP configuration


# User Environment Configuration

## Platform Information
- **Operating System**: Windows
- **Shell**: PowerShell (not Linux/Mac terminal)
- **Command Style**: Use PowerShell cmdlets and Windows commands

## PowerShell Command Discovery

### Listing Available Commands
- `Get-Command` - Lists all available cmdlets, functions, aliases, and applications
- `Get-Command | Out-GridView` - Searchable GUI for all commands
- `Get-Command Get-*` - Filter commands starting with 'Get-'
- `Get-Module -ListAvailable` - Lists all installed modules
- `Get-Command -Module <ModuleName>` - Lists commands from specific module
- `Get-Command -Type Application` - Lists traditional Windows commands (ipconfig, dir, etc.)

### Getting Help
- `Get-Help <command-name>` - Basic command help
- `Get-Help <command-name> -Detailed` - Detailed help with examples
- `Get-Help <command-name> -Examples` - Just usage examples
- `Get-Help <command-name> -Online` - Opens web documentation
- `Update-Help` - Updates local help files (may require admin)

## Windows Tool Alternatives

### JSON Handling
- Use `ConvertFrom-Json` and `ConvertTo-Json` instead of `jq`
- If `jq` needed: `winget install jq`
- Alternative: Enable WSL for Linux tool compatibility

### Common Commands
- Use `dir` or `Get-ChildItem` instead of `ls`
- Use `type` or `Get-Content` instead of `cat`
- Use `where` or `Get-Command` instead of `which`

## Reference Resources
- [PowerShell Cmdlets Documentation](https://learn.microsoft.com/en-us/powershell/module/microsoft.powershell.core/?view=powershell-7.4)
- [Windows Commands Reference](https://learn.microsoft.com/en-us/windows-server/administration/windows-commands/windows-commands)
- [PowerShell Community Resources](https://www.pdq.com/powershell/)

## Development Preferences (Inferred from Projects)

### Technology Stack
- **Framework**: Next.js 15+ with App Router pattern
- **React**: Version 19+ (latest stable)
- **TypeScript**: Strict mode enabled, path mapping with `@/*` → `./*`
- **Styling**: Tailwind CSS v4 with inline theme configuration
- **Fonts**: Geist Sans and Geist Mono from Google Fonts

### Project Structure Patterns
- **Source Organization**: `/app/` for pages, `/components/` for shared components, `/lib/` for utilities
- **Component Structure**: Separate files for each component, co-locate app-specific components in `/app/components/`
- **Content Management**: Markdown-based with frontmatter (in `content/` directory)
- **API Routes**: RESTful endpoints in `app/api/` following Next.js conventions

### Code Standards
- **TypeScript Config**: Strict mode, ES2017 target, modern module resolution
- **Import Style**: Use `@/` path mapping for clean imports
- **Component Naming**: PascalCase for components, descriptive names
- **File Extensions**: `.tsx` for React components, `.ts` for utilities

### Development Workflow
- **Dev Server**: Use Turbopack (`npm run dev --turbopack`) for faster development
- **Build Process**: Standard Next.js build pipeline
- **Linting**: ESLint with Next.js configuration (`npm run lint`)

### Styling Approach
- **CSS Variables**: Use for theming (background/foreground with dark mode support)
- **Responsive Design**: Mobile-first approach
- **Theme System**: CSS variables with system preference detection

### Content & Data Patterns
- **Static Content**: Markdown files with frontmatter metadata
- **Data Processing**: Native frontmatter parsing without external dependencies
- **API Design**: JSON endpoints for dynamic data

### SEO & Metadata Practices
- **Metadata**: Comprehensive OpenGraph, structured data, keywords
- **Sitemap**: Dynamic generation for content pages
- **Author Attribution**: Include author information in metadata

## Instructions for Claude Code
When working with this user:
1. Always use PowerShell syntax and cmdlets
2. Prefer native PowerShell commands over Linux equivalents
3. Use `Get-Help` commands to discover available options
4. Suggest Windows-native alternatives for Linux tools
5. Consider WSL when Linux-specific tools are essential
6. Follow Next.js 15+ App Router patterns
7. Use TypeScript strict mode and proper typing
8. Implement responsive, accessible designs with Tailwind CSS
9. Prefer native implementations over external dependencies when possible
10. Always run `npm run lint` after code changes