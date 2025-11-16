#!/usr/bin/env bash
################################################################################
# Emergency Database Restore Script
# Fwber Backend - Laravel Application
#
# Purpose: Rapidly restore database from most recent backup in disaster scenarios
# Use Case: Complete database loss, critical data corruption, production emergency
# Safety: Minimal prompts - USE WITH EXTREME CAUTION
#
# Usage:
#   ./emergency_restore.sh              # Restore from latest local backup
#   ./emergency_restore.sh --from-s3    # Restore from latest S3 backup
#   ./emergency_restore.sh --backup-file /path/to/backup.sql.gz
#
# Requirements:
#   - Database credentials in .env
#   - MySQL or PostgreSQL client
#   - Write access to database
#   - Optional: AWS CLI configured for S3
#
# Exit Codes:
#   0 - Restore successful
#   1 - Configuration error
#   2 - Backup file not found
#   3 - Database error
#   4 - Decompression error
#
# Author: Fwber Development Team
# Date: November 15, 2025
################################################################################

set -euo pipefail

# ============================================================================
# Configuration
# ============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="${PROJECT_ROOT}/storage/backups"
LOG_FILE="${PROJECT_ROOT}/storage/logs/emergency_restore_$(date +%Y%m%d_%H%M%S).log"

# Load environment variables
if [ -f "${PROJECT_ROOT}/.env" ]; then
    source <(grep -E '^(DB_|APP_NAME=)' "${PROJECT_ROOT}/.env" | sed 's/^/export /')
else
    echo "❌ ERROR: .env file not found at ${PROJECT_ROOT}/.env"
    exit 1
fi

# Database configuration
DB_CONNECTION="${DB_CONNECTION:-mysql}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE}"
DB_USERNAME="${DB_USERNAME}"
DB_PASSWORD="${DB_PASSWORD}"

# S3 configuration (optional)
BACKUP_S3_BUCKET="${BACKUP_S3_BUCKET:-}"
BACKUP_S3_PREFIX="${BACKUP_S3_PREFIX:-backups}"

# Command-line options
FROM_S3=false
BACKUP_FILE=""

# ============================================================================
# Logging
# ============================================================================

log() {
    local level=$1
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    echo "[$timestamp] [$level] $message" | tee -a "$LOG_FILE"
    
    case $level in
        ERROR)
            echo "❌ $message" >&2
            ;;
        SUCCESS)
            echo "✅ $message"
            ;;
        INFO)
            echo "ℹ️  $message"
            ;;
        WARNING)
            echo "⚠️  $message"
            ;;
    esac
}

# ============================================================================
# Parse Arguments
# ============================================================================

while [[ $# -gt 0 ]]; do
    case $1 in
        --from-s3)
            FROM_S3=true
            shift
            ;;
        --backup-file)
            BACKUP_FILE="$2"
            shift 2
            ;;
        --help|-h)
            cat << EOF
Emergency Database Restore Script

Usage: $0 [OPTIONS]

Options:
    --from-s3           Download and restore from latest S3 backup
    --backup-file PATH  Restore from specific backup file
    --help, -h          Show this help message

Examples:
    # Restore from latest local backup
    $0

    # Restore from latest S3 backup
    $0 --from-s3

    # Restore from specific backup
    $0 --backup-file /path/to/backup.sql.gz

WARNING: This script will DROP and RECREATE the database.
         All existing data will be PERMANENTLY LOST.
         Use with extreme caution in production environments.

EOF
            exit 0
            ;;
        *)
            log ERROR "Unknown option: $1"
            log INFO "Use --help for usage information"
            exit 1
            ;;
    esac
done

# ============================================================================
# Display Emergency Warning
# ============================================================================

clear
cat << 'EOF'
╔══════════════════════════════════════════════════════════════════════╗
║                                                                      ║
║                    ⚠️  EMERGENCY RESTORE MODE ⚠️                      ║
║                                                                      ║
║  This script will IMMEDIATELY restore the database from backup.     ║
║  ALL CURRENT DATA IN THE DATABASE WILL BE PERMANENTLY DESTROYED.    ║
║                                                                      ║
║  Use this script ONLY in disaster recovery scenarios:               ║
║    • Complete database loss                                         ║
║    • Critical data corruption                                       ║
║    • Production emergency requiring immediate rollback              ║
║                                                                      ║
║  For non-emergency restores, use restore_database.sh instead.       ║
║                                                                      ║
╚══════════════════════════════════════════════════════════════════════╝

EOF

echo ""
echo "Database Target:"
echo "  Connection: $DB_CONNECTION"
echo "  Host:       $DB_HOST:$DB_PORT"
echo "  Database:   $DB_DATABASE"
echo "  User:       $DB_USERNAME"
echo ""

# ============================================================================
# Determine Backup File
# ============================================================================

if [ -n "$BACKUP_FILE" ]; then
    # Use specified backup file
    if [ ! -f "$BACKUP_FILE" ]; then
        log ERROR "Specified backup file not found: $BACKUP_FILE"
        exit 2
    fi
    
    log INFO "Using specified backup: $BACKUP_FILE"
    
elif [ "$FROM_S3" = true ]; then
    # Download from S3
    if [ -z "$BACKUP_S3_BUCKET" ]; then
        log ERROR "BACKUP_S3_BUCKET not configured in .env"
        exit 1
    fi
    
    log INFO "Fetching latest backup from S3..."
    
    # Find latest backup in S3
    LATEST_S3_KEY=$(aws s3 ls "s3://${BACKUP_S3_BUCKET}/${BACKUP_S3_PREFIX}/" \
        | grep '\.sql\.gz$' \
        | sort \
        | tail -n 1 \
        | awk '{print $4}')
    
    if [ -z "$LATEST_S3_KEY" ]; then
        log ERROR "No backups found in S3 bucket: s3://${BACKUP_S3_BUCKET}/${BACKUP_S3_PREFIX}/"
        exit 2
    fi
    
    log INFO "Latest S3 backup: $LATEST_S3_KEY"
    
    # Download to temporary location
    BACKUP_FILE="/tmp/emergency_restore_$(date +%Y%m%d_%H%M%S).sql.gz"
    log INFO "Downloading from S3..."
    
    if ! aws s3 cp "s3://${BACKUP_S3_BUCKET}/${BACKUP_S3_PREFIX}/${LATEST_S3_KEY}" "$BACKUP_FILE"; then
        log ERROR "Failed to download backup from S3"
        exit 2
    fi
    
    log SUCCESS "Downloaded: $BACKUP_FILE"
    
else
    # Use latest local backup
    if [ ! -d "$BACKUP_DIR" ]; then
        log ERROR "Backup directory not found: $BACKUP_DIR"
        exit 2
    fi
    
    BACKUP_FILE=$(find "$BACKUP_DIR" -name "*.sql.gz" -o -name "*.sql" | sort | tail -n 1)
    
    if [ -z "$BACKUP_FILE" ]; then
        log ERROR "No backup files found in $BACKUP_DIR"
        exit 2
    fi
    
    log INFO "Using latest local backup: $BACKUP_FILE"
fi

# Get backup metadata
BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
BACKUP_DATE=$(stat -c %y "$BACKUP_FILE" 2>/dev/null || stat -f %Sm "$BACKUP_FILE" 2>/dev/null || echo "Unknown")

echo ""
echo "Backup Details:"
echo "  File: $BACKUP_FILE"
echo "  Size: $BACKUP_SIZE"
echo "  Date: $BACKUP_DATE"
echo ""

# ============================================================================
# Final Confirmation
# ============================================================================

echo "╔══════════════════════════════════════════════════════════════════════╗"
echo "║                      FINAL CONFIRMATION REQUIRED                     ║"
echo "╚══════════════════════════════════════════════════════════════════════╝"
echo ""
echo "Type 'EMERGENCY RESTORE' in ALL CAPS to proceed:"
read -r CONFIRMATION

if [ "$CONFIRMATION" != "EMERGENCY RESTORE" ]; then
    log WARNING "Restore cancelled by user"
    exit 0
fi

log INFO "Emergency restore confirmed. Starting restore process..."

# ============================================================================
# Database Connection Test
# ============================================================================

log INFO "Testing database connection..."

if [ "$DB_CONNECTION" = "mysql" ]; then
    if ! mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1" &>/dev/null; then
        log ERROR "Cannot connect to MySQL database"
        exit 3
    fi
elif [ "$DB_CONNECTION" = "pgsql" ]; then
    if ! PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d postgres -c "SELECT 1" &>/dev/null; then
        log ERROR "Cannot connect to PostgreSQL database"
        exit 3
    fi
else
    log ERROR "Unsupported database connection: $DB_CONNECTION"
    exit 1
fi

log SUCCESS "Database connection successful"

# ============================================================================
# Drop and Recreate Database
# ============================================================================

log WARNING "Dropping existing database: $DB_DATABASE"

if [ "$DB_CONNECTION" = "mysql" ]; then
    mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" <<EOF
DROP DATABASE IF EXISTS \`${DB_DATABASE}\`;
CREATE DATABASE \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EOF
    
elif [ "$DB_CONNECTION" = "pgsql" ]; then
    PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d postgres <<EOF
DROP DATABASE IF EXISTS "${DB_DATABASE}";
CREATE DATABASE "${DB_DATABASE}";
EOF
fi

log SUCCESS "Database recreated: $DB_DATABASE"

# ============================================================================
# Restore from Backup
# ============================================================================

log INFO "Starting database restore..."

START_TIME=$(date +%s)

if [[ "$BACKUP_FILE" == *.gz ]]; then
    # Compressed backup
    log INFO "Decompressing and restoring..."
    
    if [ "$DB_CONNECTION" = "mysql" ]; then
        if ! gunzip -c "$BACKUP_FILE" | mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE"; then
            log ERROR "Restore failed"
            exit 3
        fi
    elif [ "$DB_CONNECTION" = "pgsql" ]; then
        if ! gunzip -c "$BACKUP_FILE" | PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE"; then
            log ERROR "Restore failed"
            exit 3
        fi
    fi
    
else
    # Uncompressed backup
    log INFO "Restoring uncompressed backup..."
    
    if [ "$DB_CONNECTION" = "mysql" ]; then
        if ! mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < "$BACKUP_FILE"; then
            log ERROR "Restore failed"
            exit 3
        fi
    elif [ "$DB_CONNECTION" = "pgsql" ]; then
        if ! PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" < "$BACKUP_FILE"; then
            log ERROR "Restore failed"
            exit 3
        fi
    fi
fi

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

log SUCCESS "Database restored successfully in ${DURATION}s"

# ============================================================================
# Verification
# ============================================================================

log INFO "Verifying restored database..."

if [ "$DB_CONNECTION" = "mysql" ]; then
    TABLE_COUNT=$(mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -sN -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_DATABASE'")
elif [ "$DB_CONNECTION" = "pgsql" ]; then
    TABLE_COUNT=$(PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public'")
fi

if [ -z "$TABLE_COUNT" ] || [ "$TABLE_COUNT" -eq 0 ]; then
    log ERROR "Verification failed: No tables found in restored database"
    exit 3
fi

log SUCCESS "Verification passed: $TABLE_COUNT tables found"

# ============================================================================
# Cleanup
# ============================================================================

if [ "$FROM_S3" = true ] && [ -f "$BACKUP_FILE" ]; then
    log INFO "Cleaning up temporary S3 download..."
    rm -f "$BACKUP_FILE"
fi

# ============================================================================
# Post-Restore Actions
# ============================================================================

log INFO "Running post-restore tasks..."

cd "$PROJECT_ROOT"

# Run migrations (in case backup is older than current code)
if command -v php &>/dev/null && [ -f artisan ]; then
    log INFO "Running database migrations..."
    php artisan migrate --force 2>&1 | tee -a "$LOG_FILE"
    
    # Clear caches
    log INFO "Clearing application caches..."
    php artisan config:clear 2>&1 | tee -a "$LOG_FILE"
    php artisan route:clear 2>&1 | tee -a "$LOG_FILE"
    php artisan view:clear 2>&1 | tee -a "$LOG_FILE"
fi

# ============================================================================
# Success Summary
# ============================================================================

cat << EOF

╔══════════════════════════════════════════════════════════════════════╗
║                  ✅  EMERGENCY RESTORE COMPLETE                       ║
╚══════════════════════════════════════════════════════════════════════╝

Restore Summary:
  Backup File:     $BACKUP_FILE
  Backup Size:     $BACKUP_SIZE
  Database:        $DB_DATABASE
  Tables Restored: $TABLE_COUNT
  Duration:        ${DURATION}s
  Log File:        $LOG_FILE

CRITICAL POST-RESTORE ACTIONS:
  1. Test application functionality immediately
  2. Verify critical user data is present
  3. Check application logs for errors
  4. Notify team of restore completion
  5. Document incident and restore process

Next Steps:
  1. Run smoke tests: curl http://localhost:8000/health
  2. Check authentication: php artisan tinker --execute="User::count();"
  3. Verify data integrity for critical tables
  4. Monitor application logs: tail -f storage/logs/laravel.log
  5. Schedule post-mortem incident review

EOF

log SUCCESS "Emergency restore completed successfully"

exit 0
