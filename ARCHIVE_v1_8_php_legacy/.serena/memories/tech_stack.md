# Technology Stack

## Current Legacy Stack (Production Ready)
- **Backend**: PHP 8+ with PDO
- **Database**: MySQL with comprehensive schema
- **Dependencies**: Composer (PHPMailer, PHPdotenv)
- **Security**: Argon2ID, CSRF tokens, rate limiting
- **Architecture**: Manager classes (SecurityManager, ProfileManager, PhotoManager)

## Aspirational Modern Stack (Planned)
- **Backend**: Laravel 11 (fwber-backend/)
- **Frontend**: Next.js 14 (fwber-frontend/)
- **Database**: PostgreSQL (recommended) or MySQL
- **Caching**: Redis
- **AI Integration**: Google Gemini, Stable Diffusion, DALL-E
- **Deployment**: Docker containers

## Development Environment
- **Local Server**: XAMPP (Apache, MySQL, PHP)
- **Package Managers**: Composer (PHP), npm (Node.js)
- **IDE**: Cursor with Claude Code integration
- **Version Control**: Git

## File Structure
- Root: Legacy PHP application (production-ready)
- fwber-backend/: Laravel backend (aspirational)
- fwber-frontend/: Next.js frontend (aspirational)
- api/: API endpoints for dynamic features
- avatars/: AI-generated avatar assets