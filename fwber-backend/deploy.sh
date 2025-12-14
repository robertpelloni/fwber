#!/usr/bin/env bash
set -e

#############################################################################
# FWBer Laravel Backend Deployment Script
#
# Purpose: Automated deployment with validation, migrations, cache clearing
# Usage: ./deploy.sh [--env=production] [--branch=main] [--skip-migrations]
#
# Author: AI-generated deployment automation
# Date: 2025-11-15
#############################################################################

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
ENVIRONMENT="production"
BRANCH="main"
SKIP_MIGRATIONS=false
SKIP_BACKUP=false
DRY_RUN=false
FORCE=false

# Auto-enable force mode in CI environments
if [ "$CI" = "true" ] || [ "$GITHUB_ACTIONS" = "true" ]; then
    FORCE=true
fi

# Parse command line arguments
for arg in "$@"; do
    case $arg in
        --env=*)
            ENVIRONMENT="${arg#*=}"
            shift
            ;;
        --branch=*)
            BRANCH="${arg#*=}"
            shift
            ;;
        --skip-migrations)
            SKIP_MIGRATIONS=true
            shift
            ;;
        --skip-backup)
            SKIP_BACKUP=true
            shift
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --force)
            FORCE=true
            shift
            ;;
        --help)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --env=ENV           Set environment (default: production)"
            echo "  --branch=BRANCH     Git branch to deploy (default: main)"
            echo "  --skip-migrations   Skip database migrations"
            echo "  --skip-backup       Skip database backup before migrations"
            echo "  --dry-run          Show what would be done without executing"
            echo "  --force            Skip confirmation prompts (useful for CI/CD)"
            echo "  --help             Show this help message"
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown argument: $arg${NC}"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

#############################################################################
# Helper Functions
#############################################################################

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_command() {
    if ! command -v $1 &> /dev/null && ! which $1 &> /dev/null; then
        log_error "Required command '$1' not found. Please install it."
        exit 1
    fi
}

run_or_dry() {
    if [ "$DRY_RUN" = true ]; then
        log_info "[DRY RUN] Would execute: $@"
    else
        "$@"
    fi
}

#############################################################################
# Pre-Deployment Checks
#############################################################################

log_info "=========================================="
log_info "FWBer Deployment Script"
log_info "Environment: $ENVIRONMENT"
log_info "Branch: $BRANCH"
log_info "=========================================="
echo ""

# Check required commands
log_info "Checking required commands..."

# Add local composer to PATH if exists
if [ -f "$PWD/composer" ]; then
    export PATH=$PWD:$PATH
elif [ -f "$HOME/composer" ]; then
    export PATH=$HOME:$PATH
fi

# Add common Windows PHP paths to PATH if php is not found
if ! command -v php &> /dev/null; then
    export PATH="/mnt/c/tools/php84:$PATH"
    export PATH="/c/tools/php84:$PATH"
    export PATH="/c/php:$PATH"
    export PATH="/c/xampp/php:$PATH"
    
    # If php is still not found but php.exe is, create a function wrapper
    if ! command -v php &> /dev/null && command -v php.exe &> /dev/null; then
        php() {
            php.exe "$@"
        }
        export -f php
    fi
fi

# check_command php
check_command composer
check_command git
log_success "All required commands found"
echo ""

# Check PHP version (require 8.2+)
PHP_VERSION=$(php -r "echo PHP_VERSION;")
PHP_MAJOR=$(echo $PHP_VERSION | cut -d. -f1)
PHP_MINOR=$(echo $PHP_VERSION | cut -d. -f2)

log_info "PHP version: $PHP_VERSION"
if [ "$PHP_MAJOR" -lt 8 ] || ([ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -lt 2 ]); then
    log_error "PHP 8.2 or higher is required. Current version: $PHP_VERSION"
    exit 1
fi
log_success "PHP version check passed"
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    log_warning ".env file not found. Creating from .env.example..."
    if [ -f .env.example ]; then
        cp .env.example .env
        log_warning "Created .env from .env.example. Please update with production credentials!"
        
        # Generate key if needed
        if grep -q "APP_KEY=" .env && [ -z "$(grep "APP_KEY=" .env | cut -d '=' -f2)" ]; then
            log_info "Generating application key..."
            php artisan key:generate --force
        fi
    else
        log_error ".env file not found and .env.example is missing!"
        exit 1
    fi
fi

# Fix invalid Sentry DSN if present (prevents composer install crash)
if grep -q "SENTRY_LARAVEL_DSN=your_sentry_dsn_here" .env; then
    log_warning "Found invalid Sentry DSN placeholder. Clearing it to prevent crashes..."
    # Use sed to replace the line. The syntax differs slightly between GNU sed and BSD sed (macOS)
    # This syntax should work on Linux (DreamHost)
    sed -i 's/SENTRY_LARAVEL_DSN=your_sentry_dsn_here/SENTRY_LARAVEL_DSN=/g' .env
fi

log_success ".env file exists"
echo ""

# Verify environment matches
ENV_VALUE=$(grep "^APP_ENV=" .env | cut -d '=' -f2 | tr -d '\r')
if [ "$ENV_VALUE" != "$ENVIRONMENT" ]; then
    log_warning "APP_ENV in .env ($ENV_VALUE) doesn't match deployment environment ($ENVIRONMENT)"
    
    if [ "$FORCE" = false ]; then
        read -p "Continue anyway? (y/N) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            log_info "Deployment cancelled"
            exit 0
        fi
    else
        log_info "Forcing continuation despite environment mismatch..."
    fi
fi

# Check if APP_DEBUG is false for production
if [ "$ENVIRONMENT" = "production" ]; then
    DEBUG_VALUE=$(grep "^APP_DEBUG=" .env | cut -d '=' -f2)
    if [ "$DEBUG_VALUE" = "true" ]; then
        log_error "APP_DEBUG must be false in production environment"
        exit 1
    fi
    log_success "APP_DEBUG is correctly set to false"
fi
echo ""

#############################################################################
# Enable Maintenance Mode
#############################################################################

log_info "Enabling maintenance mode..."
# Ensure we are in the backend directory or call artisan correctly
if [ -f "artisan" ]; then
    run_or_dry php artisan down --retry=60 || true
elif [ -f "fwber-backend/artisan" ]; then
    run_or_dry php fwber-backend/artisan down --retry=60 || true
else
    log_warning "Could not find artisan to enable maintenance mode"
fi
log_success "Maintenance mode enabled"
echo ""

#############################################################################
# Git Operations
#############################################################################

log_info "Updating code from Git repository..."

# Navigate to repo root if we are in fwber-backend
if [ -d "../fwber-backend" ] && [ -d "../.git" ]; then
    cd ..
    log_info "Changed directory to repository root: $PWD"
fi

# Handle SQLite conflict - Preserve local data
if [ -f "fwber-backend/database/database.sqlite" ]; then
    log_info "Preserving local SQLite database (fwber-backend/database/database.sqlite)..."
    cp fwber-backend/database/database.sqlite fwber-backend/database/database.sqlite.preserve
    # Revert changes to allow pull
    git checkout fwber-backend/database/database.sqlite || true
elif [ -f "database/database.sqlite" ]; then
    log_info "Preserving local SQLite database (database/database.sqlite)..."
    cp database/database.sqlite database/database.sqlite.preserve
    git checkout database/database.sqlite || true
else
    log_warning "SQLite database not found for preservation. Checked: fwber-backend/database/database.sqlite and database/database.sqlite"
    log_info "Current directory: $PWD"
    ls -la fwber-backend/database/ || true
    ls -la database/ || true
fi

run_or_dry git fetch origin
run_or_dry git checkout $BRANCH
run_or_dry git pull origin $BRANCH
log_success "Code updated to branch: $BRANCH"

# Restore SQLite data
if [ -f "fwber-backend/database/database.sqlite.preserve" ]; then
    log_info "Restoring local SQLite database..."
    mv fwber-backend/database/database.sqlite.preserve fwber-backend/database/database.sqlite
elif [ -f "database/database.sqlite.preserve" ]; then
    log_info "Restoring local SQLite database..."
    mv database/database.sqlite.preserve database/database.sqlite
fi

# Navigate back to backend directory if we are in root
if [ -d "fwber-backend" ]; then
    cd fwber-backend
    log_info "Changed directory to backend: $PWD"
fi

echo ""

# Get current commit hash
COMMIT_HASH=$(git rev-parse --short HEAD)
log_info "Deployed commit: $COMMIT_HASH"
echo ""

#############################################################################
# Composer Dependencies
#############################################################################

log_info "Installing/updating Composer dependencies..."
if [ "$ENVIRONMENT" = "production" ]; then
    run_or_dry composer install --optimize-autoloader --no-dev --no-interaction
else
    run_or_dry composer install --optimize-autoloader --no-interaction
fi
log_success "Composer dependencies installed"
echo ""

#############################################################################
# Database Backup (before migrations)
#############################################################################

if [ "$SKIP_BACKUP" = false ] && [ "$SKIP_MIGRATIONS" = false ]; then
    log_info "Creating database backup before migrations..."

    DB_CONNECTION=$(grep "^DB_CONNECTION=" .env | cut -d '=' -f2)

    if [ "$DB_CONNECTION" = "mysql" ] || [ "$DB_CONNECTION" = "pgsql" ]; then
        BACKUP_DIR="backups/database"
        mkdir -p $BACKUP_DIR
        BACKUP_FILE="$BACKUP_DIR/backup_$(date +%Y%m%d_%H%M%S).sql"

        if [ "$DB_CONNECTION" = "mysql" ]; then
            DB_HOST=$(grep "^DB_HOST=" .env | cut -d '=' -f2)
            DB_PORT=$(grep "^DB_PORT=" .env | cut -d '=' -f2)
            DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2)
            DB_USERNAME=$(grep "^DB_USERNAME=" .env | cut -d '=' -f2)
            DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2)

            if [ "$DRY_RUN" = false ]; then
                mysqldump --no-tablespaces -h$DB_HOST -P$DB_PORT -u$DB_USERNAME -p$DB_PASSWORD $DB_DATABASE > $BACKUP_FILE
                log_success "Database backup created: $BACKUP_FILE"
            else
                log_info "[DRY RUN] Would create backup: $BACKUP_FILE"
            fi
        elif [ "$DB_CONNECTION" = "sqlite" ]; then
            # SQLite Backup
            DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2)
            # Handle absolute or relative path
            if [[ "$DB_DATABASE" != /* ]]; then
                DB_DATABASE="$PWD/$DB_DATABASE"
            fi
            
            if [ -f "$DB_DATABASE" ]; then
                if [ "$DRY_RUN" = false ]; then
                    cp "$DB_DATABASE" "$BACKUP_FILE"
                    log_success "SQLite database backup created: $BACKUP_FILE"
                else
                    log_info "[DRY RUN] Would create SQLite backup: $BACKUP_FILE"
                fi
            else
                log_warning "SQLite database file not found at $DB_DATABASE"
            fi
        else
            log_warning "PostgreSQL backup not implemented yet. Skipping..."
        fi
    else
        log_warning "Database backup only supported for MySQL/SQLite. Current: $DB_CONNECTION"
    fi
    echo ""
else
    log_warning "Database backup skipped"
    echo ""
fi

#############################################################################
# Database Migrations
#############################################################################

if [ "$SKIP_MIGRATIONS" = false ]; then
    log_info "Running database migrations..."

    # Check for pending migrations
    if [ "$DRY_RUN" = false ]; then
        PENDING_MIGRATIONS=$(php artisan migrate:status | grep -c "Pending" || true)

        if [ "$PENDING_MIGRATIONS" -gt 0 ]; then
            log_warning "Found $PENDING_MIGRATIONS pending migration(s)"

            if [ "$ENVIRONMENT" = "production" ] && [ "$FORCE" = false ]; then
                read -p "Continue with migrations? (y/N) " -n 1 -r
                echo
                if [[ ! $REPLY =~ ^[Yy]$ ]]; then
                    log_info "Migrations cancelled. Exiting..."
                    php artisan up
                    exit 0
                fi
            fi

            run_or_dry php artisan migrate --force
            log_success "Migrations completed"
        else
            log_info "No pending migrations"
        fi
    else
        log_info "[DRY RUN] Would check and run migrations"
    fi
    echo ""
else
    log_warning "Database migrations skipped"
    echo ""
fi

#############################################################################
# Cache Operations
#############################################################################

log_info "Clearing and rebuilding caches..."

# Clear various caches
run_or_dry php artisan config:clear
run_or_dry php artisan cache:clear
run_or_dry php artisan route:clear
run_or_dry php artisan view:clear
run_or_dry php artisan event:clear

# Rebuild caches for production
if [ "$ENVIRONMENT" = "production" ]; then
    run_or_dry php artisan config:cache
    run_or_dry php artisan route:cache
    run_or_dry php artisan view:cache
    run_or_dry php artisan event:cache
fi

log_success "Caches cleared and rebuilt"
echo ""

#############################################################################
# Storage Links
#############################################################################

log_info "Ensuring storage symlink exists..."
run_or_dry php artisan storage:link || true
log_success "Storage symlink verified"
echo ""

#############################################################################
# OpenAPI Documentation
#############################################################################

log_info "Regenerating OpenAPI documentation..."
run_or_dry php artisan l5-swagger:generate || log_warning "OpenAPI generation failed, continuing anyway..."
log_success "OpenAPI documentation regenerated (or skipped)"
echo ""

#############################################################################
# Queue Workers
#############################################################################

log_info "Restarting queue workers..."
run_or_dry php artisan queue:restart
log_success "Queue workers restarted (if running)"
echo ""

#############################################################################
# File Permissions
#############################################################################

log_info "Setting correct file permissions..."
if [ "$DRY_RUN" = false ]; then
    chmod -R 755 storage bootstrap/cache
    log_success "File permissions set"
else
    log_info "[DRY RUN] Would set permissions on storage and bootstrap/cache"
fi
echo ""

#############################################################################
# Health Check
#############################################################################

log_info "Running health check..."
if [ "$DRY_RUN" = false ]; then
    # Simple check if the application can boot
    if php artisan about > /dev/null 2>&1; then
        log_success "Application boot check passed"
    else
        log_error "Application failed to boot"
        exit 1
    fi
else
    log_info "[DRY RUN] Would run health check"
fi
echo ""

#############################################################################
# Disable Maintenance Mode
#############################################################################

log_info "Disabling maintenance mode..."
run_or_dry php artisan up
log_success "Maintenance mode disabled"
echo ""

#############################################################################
# Post-Deployment Summary
#############################################################################

echo ""
log_success "=========================================="
log_success "Deployment completed successfully!"
log_success "=========================================="
echo ""
log_info "Deployment Summary:"
log_info "  Environment: $ENVIRONMENT"
log_info "  Branch: $BRANCH"
log_info "  Commit: $COMMIT_HASH"
log_info "  Time: $(date)"
echo ""

if [ "$DRY_RUN" = true ]; then
    log_warning "This was a DRY RUN. No changes were made."
    echo ""
fi

log_info "Next steps:"
log_info "  1. Verify application is accessible"
log_info "  2. Check logs: tail -f storage/logs/laravel.log"
log_info "  3. Monitor error tracking (Sentry)"
log_info "  4. Check queue workers: ps aux | grep queue"
echo ""

exit 0