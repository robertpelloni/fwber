#!/bin/bash

################################################################################
# Database Backup Script for FWBER
################################################################################
#
# Purpose: Create automated backups of MySQL/PostgreSQL databases with
#          compression, retention policy, and S3/local storage support.
#
# Usage:
#   ./backup_database.sh [options]
#
# Options:
#   --config FILE      Path to .env file (default: ../.env)
#   --output DIR       Local backup directory (default: ../backups/database)
#   --compress         Compress backup with gzip (default: true)
#   --s3-upload        Upload backup to S3 (requires AWS_* env vars)
#   --retention DAYS   Number of days to keep backups (default: 30)
#   --verify           Verify backup integrity after creation
#   --quiet            Suppress informational output
#   --help             Show this help message
#
# Examples:
#   ./backup_database.sh
#   ./backup_database.sh --config /path/to/.env --output /backups
#   ./backup_database.sh --s3-upload --retention 90
#   ./backup_database.sh --verify --quiet
#
# Exit Codes:
#   0 - Success
#   1 - Configuration error
#   2 - Backup failed
#   3 - Verification failed
#   4 - S3 upload failed
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
OUTPUT_DIR="${SCRIPT_DIR}/../backups/database"
COMPRESS=true
S3_UPLOAD=false
RETENTION_DAYS=30
VERIFY=false
QUIET=false

# Logging functions
log_info() {
    if [ "$QUIET" = false ]; then
        echo -e "${BLUE}ℹ ${NC}$1"
    fi
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
        --config)
            CONFIG_FILE="$2"
            shift 2
            ;;
        --output)
            OUTPUT_DIR="$2"
            shift 2
            ;;
        --compress)
            COMPRESS=true
            shift
            ;;
        --no-compress)
            COMPRESS=false
            shift
            ;;
        --s3-upload)
            S3_UPLOAD=true
            shift
            ;;
        --retention)
            RETENTION_DAYS="$2"
            shift 2
            ;;
        --verify)
            VERIFY=true
            shift
            ;;
        --quiet)
            QUIET=true
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

log_info "Starting FWBER database backup..."
echo ""

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

# Create output directory
mkdir -p "$OUTPUT_DIR"

# Verify output directory is writable
if [ ! -w "$OUTPUT_DIR" ]; then
    log_error "Output directory not writable: $OUTPUT_DIR"
    exit 1
fi

log_success "Configuration validated"
echo ""

#############################################################################
# Backup Configuration
#############################################################################

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="fwber_${DB_CONNECTION}_${TIMESTAMP}"
BACKUP_FILE="${OUTPUT_DIR}/${BACKUP_NAME}.sql"

if [ "$COMPRESS" = true ]; then
    BACKUP_FILE="${BACKUP_FILE}.gz"
fi

log_info "Backup configuration:"
log_info "  Database type: $DB_CONNECTION"
log_info "  Backup file: $BACKUP_FILE"
log_info "  Compression: $COMPRESS"
log_info "  S3 upload: $S3_UPLOAD"
log_info "  Retention: $RETENTION_DAYS days"
log_info "  Verification: $VERIFY"
echo ""

#############################################################################
# MySQL Backup
#############################################################################

backup_mysql() {
    log_info "Creating MySQL backup..."
    
    # Validate MySQL environment variables
    if [ -z "${DB_HOST:-}" ] || [ -z "${DB_DATABASE:-}" ] || [ -z "${DB_USERNAME:-}" ] || [ -z "${DB_PASSWORD:-}" ]; then
        log_error "Missing MySQL configuration (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)"
        exit 1
    fi
    
    DB_PORT="${DB_PORT:-3306}"
    
    # Build mysqldump command
    MYSQLDUMP_CMD="mysqldump"
    MYSQLDUMP_CMD="$MYSQLDUMP_CMD --host=$DB_HOST"
    MYSQLDUMP_CMD="$MYSQLDUMP_CMD --port=$DB_PORT"
    MYSQLDUMP_CMD="$MYSQLDUMP_CMD --user=$DB_USERNAME"
    MYSQLDUMP_CMD="$MYSQLDUMP_CMD --password=$DB_PASSWORD"
    
    # Add options for consistency and performance
    MYSQLDUMP_CMD="$MYSQLDUMP_CMD --single-transaction"  # InnoDB consistent backup
    MYSQLDUMP_CMD="$MYSQLDUMP_CMD --routines"            # Include stored procedures
    MYSQLDUMP_CMD="$MYSQLDUMP_CMD --triggers"            # Include triggers
    MYSQLDUMP_CMD="$MYSQLDUMP_CMD --events"              # Include events
    MYSQLDUMP_CMD="$MYSQLDUMP_CMD --quick"               # Stream results (memory efficient)
    MYSQLDUMP_CMD="$MYSQLDUMP_CMD --lock-tables=false"   # Don't lock tables (use with single-transaction)
    MYSQLDUMP_CMD="$MYSQLDUMP_CMD --add-drop-table"      # Add DROP TABLE before CREATE
    MYSQLDUMP_CMD="$MYSQLDUMP_CMD --extended-insert"     # Use multi-row INSERT (faster restore)
    MYSQLDUMP_CMD="$MYSQLDUMP_CMD --default-character-set=utf8mb4"
    
    MYSQLDUMP_CMD="$MYSQLDUMP_CMD $DB_DATABASE"
    
    # Execute backup
    if [ "$COMPRESS" = true ]; then
        if $MYSQLDUMP_CMD | gzip > "$BACKUP_FILE"; then
            log_success "MySQL backup created: $BACKUP_FILE"
        else
            log_error "MySQL backup failed"
            exit 2
        fi
    else
        if $MYSQLDUMP_CMD > "$BACKUP_FILE"; then
            log_success "MySQL backup created: $BACKUP_FILE"
        else
            log_error "MySQL backup failed"
            exit 2
        fi
    fi
}

#############################################################################
# PostgreSQL Backup
#############################################################################

backup_postgresql() {
    log_info "Creating PostgreSQL backup..."
    
    # Validate PostgreSQL environment variables
    if [ -z "${DB_HOST:-}" ] || [ -z "${DB_DATABASE:-}" ] || [ -z "${DB_USERNAME:-}" ] || [ -z "${DB_PASSWORD:-}" ]; then
        log_error "Missing PostgreSQL configuration (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)"
        exit 1
    fi
    
    DB_PORT="${DB_PORT:-5432}"
    
    # Set PGPASSWORD for non-interactive authentication
    export PGPASSWORD="$DB_PASSWORD"
    
    # Build pg_dump command
    PGDUMP_CMD="pg_dump"
    PGDUMP_CMD="$PGDUMP_CMD --host=$DB_HOST"
    PGDUMP_CMD="$PGDUMP_CMD --port=$DB_PORT"
    PGDUMP_CMD="$PGDUMP_CMD --username=$DB_USERNAME"
    PGDUMP_CMD="$PGDUMP_CMD --no-password"
    
    # Add options for consistency and performance
    PGDUMP_CMD="$PGDUMP_CMD --format=plain"           # Plain SQL format
    PGDUMP_CMD="$PGDUMP_CMD --no-owner"               # Don't dump ownership
    PGDUMP_CMD="$PGDUMP_CMD --no-privileges"          # Don't dump privileges
    PGDUMP_CMD="$PGDUMP_CMD --create"                 # Include CREATE DATABASE
    PGDUMP_CMD="$PGDUMP_CMD --clean"                  # Include DROP statements
    PGDUMP_CMD="$PGDUMP_CMD --if-exists"              # Add IF EXISTS to DROP
    PGDUMP_CMD="$PGDUMP_CMD --encoding=UTF8"
    
    PGDUMP_CMD="$PGDUMP_CMD $DB_DATABASE"
    
    # Execute backup
    if [ "$COMPRESS" = true ]; then
        if $PGDUMP_CMD | gzip > "$BACKUP_FILE"; then
            log_success "PostgreSQL backup created: $BACKUP_FILE"
        else
            log_error "PostgreSQL backup failed"
            unset PGPASSWORD
            exit 2
        fi
    else
        if $PGDUMP_CMD > "$BACKUP_FILE"; then
            log_success "PostgreSQL backup created: $BACKUP_FILE"
        else
            log_error "PostgreSQL backup failed"
            unset PGPASSWORD
            exit 2
        fi
    fi
    
    unset PGPASSWORD
}

#############################################################################
# Execute Backup
#############################################################################

START_TIME=$(date +%s)

case "$DB_CONNECTION" in
    mysql)
        backup_mysql
        ;;
    pgsql)
        backup_postgresql
        ;;
    sqlite)
        log_error "SQLite backups not supported by this script (use file copy instead)"
        exit 1
        ;;
    *)
        log_error "Unsupported database connection: $DB_CONNECTION"
        exit 1
        ;;
esac

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

# Get backup file size
BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)

log_success "Backup completed in ${DURATION}s (size: $BACKUP_SIZE)"
echo ""

#############################################################################
# Verify Backup
#############################################################################

if [ "$VERIFY" = true ]; then
    log_info "Verifying backup integrity..."
    
    if [ "$COMPRESS" = true ]; then
        # Test gzip integrity
        if gzip -t "$BACKUP_FILE" 2>/dev/null; then
            log_success "Backup file integrity verified (gzip test passed)"
        else
            log_error "Backup file is corrupted (gzip test failed)"
            exit 3
        fi
        
        # Check if SQL is valid (basic check - look for SQL keywords)
        if zcat "$BACKUP_FILE" | head -n 100 | grep -q "CREATE\|INSERT\|DROP"; then
            log_success "Backup content appears valid (SQL keywords found)"
        else
            log_warning "Backup content verification inconclusive"
        fi
    else
        # Check if SQL is valid
        if head -n 100 "$BACKUP_FILE" | grep -q "CREATE\|INSERT\|DROP"; then
            log_success "Backup content appears valid (SQL keywords found)"
        else
            log_error "Backup file does not appear to contain valid SQL"
            exit 3
        fi
    fi
    
    echo ""
fi

#############################################################################
# S3 Upload
#############################################################################

if [ "$S3_UPLOAD" = true ]; then
    log_info "Uploading backup to S3..."
    
    # Validate AWS configuration
    if [ -z "${AWS_ACCESS_KEY_ID:-}" ] || [ -z "${AWS_SECRET_ACCESS_KEY:-}" ] || [ -z "${AWS_BUCKET:-}" ]; then
        log_error "Missing AWS configuration (AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_BUCKET)"
        exit 4
    fi
    
    AWS_REGION="${AWS_DEFAULT_REGION:-us-east-1}"
    S3_PREFIX="${S3_BACKUP_PREFIX:-backups/database}"
    S3_PATH="s3://${AWS_BUCKET}/${S3_PREFIX}/$(basename "$BACKUP_FILE")"
    
    # Check if aws-cli is installed
    if ! command -v aws &> /dev/null; then
        log_error "aws-cli not installed. Install with: pip install awscli"
        exit 4
    fi
    
    # Upload to S3
    if aws s3 cp "$BACKUP_FILE" "$S3_PATH" --region "$AWS_REGION" --storage-class STANDARD_IA; then
        log_success "Backup uploaded to S3: $S3_PATH"
        
        # Set lifecycle policy (optional - informational)
        log_info "S3 Lifecycle: Consider setting up lifecycle policies for automatic archival"
        log_info "  30 days: STANDARD_IA"
        log_info "  90 days: GLACIER"
        log_info "  365 days: DELETE"
    else
        log_error "S3 upload failed"
        exit 4
    fi
    
    echo ""
fi

#############################################################################
# Cleanup Old Backups
#############################################################################

log_info "Cleaning up old backups (retention: $RETENTION_DAYS days)..."

# Find and delete backups older than retention period
DELETED_COUNT=0
while IFS= read -r -d '' OLD_BACKUP; do
    rm -f "$OLD_BACKUP"
    DELETED_COUNT=$((DELETED_COUNT + 1))
    log_info "  Deleted: $(basename "$OLD_BACKUP")"
done < <(find "$OUTPUT_DIR" -name "fwber_*.sql*" -type f -mtime +$RETENTION_DAYS -print0 2>/dev/null || true)

if [ $DELETED_COUNT -gt 0 ]; then
    log_success "Deleted $DELETED_COUNT old backup(s)"
else
    log_info "No old backups to delete"
fi

echo ""

#############################################################################
# Summary
#############################################################################

log_success "Backup Summary:"
log_success "  Backup file: $BACKUP_FILE"
log_success "  Size: $BACKUP_SIZE"
log_success "  Duration: ${DURATION}s"
log_success "  Timestamp: $(date)"
if [ "$S3_UPLOAD" = true ]; then
    log_success "  S3 location: $S3_PATH"
fi

echo ""
log_success "Backup completed successfully!"

exit 0
