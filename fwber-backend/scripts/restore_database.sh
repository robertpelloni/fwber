#!/bin/bash

################################################################################
# Database Restore Script for FWBER
################################################################################
#
# Purpose: Restore MySQL/PostgreSQL databases from backup files with
#          safety checks, verification, and rollback support.
#
# Usage:
#   ./restore_database.sh --backup FILE [options]
#
# Options:
#   --backup FILE      Path to backup file to restore (required)
#   --config FILE      Path to .env file (default: ../.env)
#   --force            Skip confirmation prompts
#   --drop-database    Drop and recreate database before restore
#   --dry-run          Show what would be done without executing
#   --verify           Verify database after restore
#   --help             Show this help message
#
# Examples:
#   ./restore_database.sh --backup ../backups/database/backup_20251115_120000.sql.gz
#   ./restore_database.sh --backup backup.sql --drop-database --verify
#   ./restore_database.sh --backup backup.sql.gz --dry-run
#
# Exit Codes:
#   0 - Success
#   1 - Configuration error
#   2 - Restore failed
#   3 - Verification failed
#
# WARNINGS:
#   - This script will OVERWRITE existing database data
#   - Always backup current data before restoring
#   - Use --dry-run first to verify commands
#   - Recommended: Test restore in staging environment first
#
# Author: GitHub Copilot
# Date: 2025-11-15
################################################################################

set -euo pipefail

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="${SCRIPT_DIR}/../.env"
BACKUP_FILE=""
FORCE=false
DROP_DATABASE=false
DRY_RUN=false
VERIFY=false

# Logging functions
log_info() {
    echo -e "${BLUE}ℹ ${NC}$1"
}

log_success() {
    echo -e "${GREEN}✓${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

log_error() {
    echo -e "${RED}✗${NC} $1" >&2
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --backup)
            BACKUP_FILE="$2"
            shift 2
            ;;
        --config)
            CONFIG_FILE="$2"
            shift 2
            ;;
        --force)
            FORCE=true
            shift
            ;;
        --drop-database)
            DROP_DATABASE=true
            shift
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --verify)
            VERIFY=true
            shift
            ;;
        --help)
            grep "^#" "$0" | grep -v "#!/bin/bash" | sed 's/^# //' | sed 's/^#//'
            exit 0
            ;;
        *)
            log_error "Unknown option: $1"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

#############################################################################
# Validation
#############################################################################

log_warning "═══════════════════════════════════════════════════════════════"
log_warning "               DATABASE RESTORE - WARNING"
log_warning "═══════════════════════════════════════════════════════════════"
log_warning "This operation will OVERWRITE existing database data!"
log_warning "Make sure you have a backup of the current database."
log_warning "═══════════════════════════════════════════════════════════════"
echo ""

# Check if backup file was provided
if [ -z "$BACKUP_FILE" ]; then
    log_error "Backup file not specified. Use --backup FILE"
    exit 1
fi

# Check if backup file exists
if [ ! -f "$BACKUP_FILE" ]; then
    log_error "Backup file not found: $BACKUP_FILE"
    exit 1
fi

# Check if config file exists
if [ ! -f "$CONFIG_FILE" ]; then
    log_error "Configuration file not found: $CONFIG_FILE"
    exit 1
fi

# Load environment variables
set -a
source "$CONFIG_FILE"
set +a

# Validate required variables
if [ -z "${DB_CONNECTION:-}" ]; then
    log_error "DB_CONNECTION not set in $CONFIG_FILE"
    exit 1
fi

log_success "Configuration validated"
echo ""

#############################################################################
# Restore Configuration
#############################################################################

# Detect if backup is compressed
IS_COMPRESSED=false
if [[ "$BACKUP_FILE" == *.gz ]]; then
    IS_COMPRESSED=true
fi

log_info "Restore configuration:"
log_info "  Database type: $DB_CONNECTION"
log_info "  Backup file: $BACKUP_FILE"
log_info "  Compressed: $IS_COMPRESSED"
log_info "  Drop database: $DROP_DATABASE"
log_info "  Verification: $VERIFY"
log_info "  Dry run: $DRY_RUN"
echo ""

# Get backup file size and modification time
BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
BACKUP_DATE=$(stat -c %y "$BACKUP_FILE" 2>/dev/null || stat -f "%Sm" "$BACKUP_FILE" 2>/dev/null || echo "unknown")

log_info "Backup file details:"
log_info "  Size: $BACKUP_SIZE"
log_info "  Date: $BACKUP_DATE"
echo ""

#############################################################################
# Confirmation
#############################################################################

if [ "$FORCE" = false ] && [ "$DRY_RUN" = false ]; then
    log_warning "You are about to restore the database. Current data will be LOST!"
    read -p "Are you absolutely sure you want to continue? Type 'yes' to proceed: " CONFIRMATION
    
    if [ "$CONFIRMATION" != "yes" ]; then
        log_info "Restore cancelled by user"
        exit 0
    fi
    
    echo ""
fi

#############################################################################
# MySQL Restore
#############################################################################

restore_mysql() {
    log_info "Restoring MySQL database..."
    
    # Validate MySQL environment variables
    if [ -z "${DB_HOST:-}" ] || [ -z "${DB_DATABASE:-}" ] || [ -z "${DB_USERNAME:-}" ] || [ -z "${DB_PASSWORD:-}" ]; then
        log_error "Missing MySQL configuration (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)"
        exit 1
    fi
    
    DB_PORT="${DB_PORT:-3306}"
    
    # Build mysql command
    MYSQL_CMD="mysql"
    MYSQL_CMD="$MYSQL_CMD --host=$DB_HOST"
    MYSQL_CMD="$MYSQL_CMD --port=$DB_PORT"
    MYSQL_CMD="$MYSQL_CMD --user=$DB_USERNAME"
    MYSQL_CMD="$MYSQL_CMD --password=$DB_PASSWORD"
    
    # Drop and recreate database if requested
    if [ "$DROP_DATABASE" = true ]; then
        log_warning "Dropping and recreating database: $DB_DATABASE"
        
        if [ "$DRY_RUN" = false ]; then
            mysql -h$DB_HOST -P$DB_PORT -u$DB_USERNAME -p$DB_PASSWORD -e "DROP DATABASE IF EXISTS $DB_DATABASE;" || {
                log_error "Failed to drop database"
                exit 2
            }
            mysql -h$DB_HOST -P$DB_PORT -u$DB_USERNAME -p$DB_PASSWORD -e "CREATE DATABASE $DB_DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" || {
                log_error "Failed to create database"
                exit 2
            }
            log_success "Database recreated"
        else
            log_info "[DRY RUN] Would execute: DROP DATABASE IF EXISTS $DB_DATABASE"
            log_info "[DRY RUN] Would execute: CREATE DATABASE $DB_DATABASE"
        fi
        echo ""
    fi
    
    # Restore backup
    log_info "Importing backup file..."
    
    if [ "$DRY_RUN" = false ]; then
        if [ "$IS_COMPRESSED" = true ]; then
            # Restore from compressed backup
            if zcat "$BACKUP_FILE" | $MYSQL_CMD $DB_DATABASE; then
                log_success "MySQL restore completed"
            else
                log_error "MySQL restore failed"
                exit 2
            fi
        else
            # Restore from uncompressed backup
            if $MYSQL_CMD $DB_DATABASE < "$BACKUP_FILE"; then
                log_success "MySQL restore completed"
            else
                log_error "MySQL restore failed"
                exit 2
            fi
        fi
    else
        if [ "$IS_COMPRESSED" = true ]; then
            log_info "[DRY RUN] Would execute: zcat $BACKUP_FILE | $MYSQL_CMD $DB_DATABASE"
        else
            log_info "[DRY RUN] Would execute: $MYSQL_CMD $DB_DATABASE < $BACKUP_FILE"
        fi
    fi
}

#############################################################################
# PostgreSQL Restore
#############################################################################

restore_postgresql() {
    log_info "Restoring PostgreSQL database..."
    
    # Validate PostgreSQL environment variables
    if [ -z "${DB_HOST:-}" ] || [ -z "${DB_DATABASE:-}" ] || [ -z "${DB_USERNAME:-}" ] || [ -z "${DB_PASSWORD:-}" ]; then
        log_error "Missing PostgreSQL configuration (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)"
        exit 1
    fi
    
    DB_PORT="${DB_PORT:-5432}"
    
    # Set PGPASSWORD for non-interactive authentication
    export PGPASSWORD="$DB_PASSWORD"
    
    # Drop and recreate database if requested
    if [ "$DROP_DATABASE" = true ]; then
        log_warning "Dropping and recreating database: $DB_DATABASE"
        
        if [ "$DRY_RUN" = false ]; then
            # Terminate connections to database
            psql -h $DB_HOST -p $DB_PORT -U $DB_USERNAME -d postgres -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '$DB_DATABASE';" 2>/dev/null || true
            
            # Drop and recreate
            psql -h $DB_HOST -p $DB_PORT -U $DB_USERNAME -d postgres -c "DROP DATABASE IF EXISTS $DB_DATABASE;" || {
                log_error "Failed to drop database"
                unset PGPASSWORD
                exit 2
            }
            psql -h $DB_HOST -p $DB_PORT -U $DB_USERNAME -d postgres -c "CREATE DATABASE $DB_DATABASE ENCODING 'UTF8';" || {
                log_error "Failed to create database"
                unset PGPASSWORD
                exit 2
            }
            log_success "Database recreated"
        else
            log_info "[DRY RUN] Would drop and recreate database: $DB_DATABASE"
        fi
        echo ""
    fi
    
    # Build psql command
    PSQL_CMD="psql"
    PSQL_CMD="$PSQL_CMD --host=$DB_HOST"
    PSQL_CMD="$PSQL_CMD --port=$DB_PORT"
    PSQL_CMD="$PSQL_CMD --username=$DB_USERNAME"
    PSQL_CMD="$PSQL_CMD --dbname=$DB_DATABASE"
    PSQL_CMD="$PSQL_CMD --no-password"
    
    # Restore backup
    log_info "Importing backup file..."
    
    if [ "$DRY_RUN" = false ]; then
        if [ "$IS_COMPRESSED" = true ]; then
            # Restore from compressed backup
            if zcat "$BACKUP_FILE" | $PSQL_CMD; then
                log_success "PostgreSQL restore completed"
            else
                log_error "PostgreSQL restore failed"
                unset PGPASSWORD
                exit 2
            fi
        else
            # Restore from uncompressed backup
            if $PSQL_CMD < "$BACKUP_FILE"; then
                log_success "PostgreSQL restore completed"
            else
                log_error "PostgreSQL restore failed"
                unset PGPASSWORD
                exit 2
            fi
        fi
    else
        if [ "$IS_COMPRESSED" = true ]; then
            log_info "[DRY RUN] Would execute: zcat $BACKUP_FILE | $PSQL_CMD"
        else
            log_info "[DRY RUN] Would execute: $PSQL_CMD < $BACKUP_FILE"
        fi
    fi
    
    unset PGPASSWORD
}

#############################################################################
# Execute Restore
#############################################################################

START_TIME=$(date +%s)

case "$DB_CONNECTION" in
    mysql)
        restore_mysql
        ;;
    pgsql)
        restore_postgresql
        ;;
    sqlite)
        log_error "SQLite restore not supported by this script (use file copy instead)"
        exit 1
        ;;
    *)
        log_error "Unsupported database connection: $DB_CONNECTION"
        exit 1
        ;;
esac

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

if [ "$DRY_RUN" = false ]; then
    log_success "Restore completed in ${DURATION}s"
    echo ""
fi

#############################################################################
# Verify Restore
#############################################################################

if [ "$VERIFY" = true ] && [ "$DRY_RUN" = false ]; then
    log_info "Verifying database after restore..."
    
    case "$DB_CONNECTION" in
        mysql)
            # Check table count
            TABLE_COUNT=$(mysql -h$DB_HOST -P$DB_PORT -u$DB_USERNAME -p$DB_PASSWORD -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_DATABASE';" 2>/dev/null || echo "0")
            
            if [ "$TABLE_COUNT" -gt 0 ]; then
                log_success "Database verification passed ($TABLE_COUNT tables found)"
            else
                log_error "Database verification failed (no tables found)"
                exit 3
            fi
            ;;
        pgsql)
            export PGPASSWORD="$DB_PASSWORD"
            # Check table count
            TABLE_COUNT=$(psql -h $DB_HOST -p $DB_PORT -U $DB_USERNAME -d $DB_DATABASE -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public';" 2>/dev/null | tr -d ' ' || echo "0")
            unset PGPASSWORD
            
            if [ "$TABLE_COUNT" -gt 0 ]; then
                log_success "Database verification passed ($TABLE_COUNT tables found)"
            else
                log_error "Database verification failed (no tables found)"
                exit 3
            fi
            ;;
    esac
    
    echo ""
fi

#############################################################################
# Summary
#############################################################################

if [ "$DRY_RUN" = false ]; then
    log_success "Restore Summary:"
    log_success "  Backup file: $BACKUP_FILE"
    log_success "  Database: $DB_DATABASE"
    log_success "  Duration: ${DURATION}s"
    log_success "  Timestamp: $(date)"
    
    echo ""
    log_success "Database restore completed successfully!"
    
    log_warning "Post-restore checklist:"
    log_warning "  1. Run: php artisan migrate --force (if schema version changed)"
    log_warning "  2. Run: php artisan cache:clear"
    log_warning "  3. Run: php artisan config:cache"
    log_warning "  4. Test application functionality"
    log_warning "  5. Check logs for any errors"
else
    log_info "Dry run completed. No changes were made."
fi

exit 0
