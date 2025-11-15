#!/usr/bin/env bash
set -e

#############################################################################
# FWBer Rollback Script
# 
# Purpose: Safely rollback to previous deployment
# Usage: ./rollback.sh [--to-commit=abc123] [--with-db]
#
# Author: AI-generated deployment automation
# Date: 2025-11-15
#############################################################################

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Default values
TARGET_COMMIT=""
ROLLBACK_DB=false
DRY_RUN=false

# Parse arguments
for arg in "$@"; do
    case $arg in
        --to-commit=*)
            TARGET_COMMIT="${arg#*=}"
            shift
            ;;
        --with-db)
            ROLLBACK_DB=true
            shift
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --help)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --to-commit=HASH    Commit hash to rollback to (default: previous commit)"
            echo "  --with-db          Also rollback database migrations"
            echo "  --dry-run          Show what would be done"
            echo "  --help             Show this help message"
            exit 0
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

run_or_dry() {
    if [ "$DRY_RUN" = true ]; then
        log_info "[DRY RUN] Would execute: $@"
    else
        "$@"
    fi
}

#############################################################################
# Pre-Rollback Checks
#############################################################################

log_info "=========================================="
log_info "FWBer Rollback Script"
log_info "=========================================="
echo ""

# Get current commit
CURRENT_COMMIT=$(git rev-parse --short HEAD)
log_info "Current commit: $CURRENT_COMMIT"

# Determine target commit
if [ -z "$TARGET_COMMIT" ]; then
    TARGET_COMMIT=$(git rev-parse --short HEAD~1)
    log_info "Target commit not specified, using previous: $TARGET_COMMIT"
else
    log_info "Target commit: $TARGET_COMMIT"
fi

# Verify target commit exists
if ! git rev-parse --verify $TARGET_COMMIT &> /dev/null; then
    log_error "Target commit $TARGET_COMMIT does not exist"
    exit 1
fi

# Show what will change
echo ""
log_info "Changes between current and target:"
git log --oneline $TARGET_COMMIT..$CURRENT_COMMIT
echo ""

# Confirm rollback
if [ "$DRY_RUN" = false ]; then
    log_warning "This will rollback your application to commit $TARGET_COMMIT"
    read -p "Are you sure you want to continue? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log_info "Rollback cancelled"
        exit 0
    fi
fi

#############################################################################
# Enable Maintenance Mode
#############################################################################

log_info "Enabling maintenance mode..."
run_or_dry php artisan down --retry=60 || true
log_success "Maintenance mode enabled"
echo ""

#############################################################################
# Rollback Code
#############################################################################

log_info "Rolling back code to commit $TARGET_COMMIT..."
run_or_dry git checkout $TARGET_COMMIT
log_success "Code rolled back"
echo ""

#############################################################################
# Composer Dependencies
#############################################################################

log_info "Reinstalling Composer dependencies..."
run_or_dry composer install --optimize-autoloader --no-dev --no-interaction
log_success "Composer dependencies reinstalled"
echo ""

#############################################################################
# Database Rollback (Optional)
#############################################################################

if [ "$ROLLBACK_DB" = true ]; then
    log_warning "Database rollback requested"
    log_warning "This will rollback the LAST migration batch"
    echo ""
    
    if [ "$DRY_RUN" = false ]; then
        read -p "Continue with database rollback? (y/N) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            run_or_dry php artisan migrate:rollback --force
            log_success "Database rolled back"
        else
            log_info "Database rollback skipped"
        fi
    else
        log_info "[DRY RUN] Would rollback database migrations"
    fi
    echo ""
else
    log_info "Database rollback not requested (use --with-db to include)"
    echo ""
fi

#############################################################################
# Cache Operations
#############################################################################

log_info "Clearing and rebuilding caches..."
run_or_dry php artisan config:clear
run_or_dry php artisan cache:clear
run_or_dry php artisan route:clear
run_or_dry php artisan view:clear
run_or_dry php artisan config:cache
run_or_dry php artisan route:cache
run_or_dry php artisan view:cache
log_success "Caches rebuilt"
echo ""

#############################################################################
# Queue Workers
#############################################################################

log_info "Restarting queue workers..."
run_or_dry php artisan queue:restart
log_success "Queue workers restarted"
echo ""

#############################################################################
# Disable Maintenance Mode
#############################################################################

log_info "Disabling maintenance mode..."
run_or_dry php artisan up
log_success "Maintenance mode disabled"
echo ""

#############################################################################
# Summary
#############################################################################

log_success "=========================================="
log_success "Rollback completed successfully!"
log_success "=========================================="
echo ""
log_info "Rollback Summary:"
log_info "  From commit: $CURRENT_COMMIT"
log_info "  To commit: $TARGET_COMMIT"
log_info "  Database rolled back: $ROLLBACK_DB"
log_info "  Time: $(date)"
echo ""

log_warning "IMPORTANT: Verify application functionality"
echo ""

exit 0
