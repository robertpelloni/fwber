#!/bin/bash

# Database Backup Script
# Usage: ./backup_db.sh [s3-bucket-name]

set -e

TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/tmp/fwber_backups"
DB_CONNECTION=$(grep DB_CONNECTION .env | cut -d '=' -f2)
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)
DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)

mkdir -p $BACKUP_DIR

echo "📦 Starting backup at $TIMESTAMP..."

if [ "$DB_CONNECTION" == "mysql" ]; then
    FILENAME="backup_${TIMESTAMP}.sql"
    mysqldump -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE > "$BACKUP_DIR/$FILENAME"
elif [ "$DB_CONNECTION" == "sqlite" ]; then
    FILENAME="backup_${TIMESTAMP}.sqlite"
    cp $DB_DATABASE "$BACKUP_DIR/$FILENAME"
else
    echo "❌ Unsupported database driver: $DB_CONNECTION"
    exit 1
fi

# Compress
echo "🗜️ Compressing..."
gzip "$BACKUP_DIR/$FILENAME"
COMPRESSED_FILE="$BACKUP_DIR/$FILENAME.gz"

echo "✅ Backup created: $COMPRESSED_FILE"

# Upload to S3 (Mock/Optional)
BUCKET=${1:-}
if [ ! -z "$BUCKET" ]; then
    echo "☁️ Uploading to S3 bucket: $BUCKET..."
    # aws s3 cp $COMPRESSED_FILE s3://$BUCKET/backups/
    echo "⚠️ AWS CLI not configured/mocked. Skipping upload."
else
    echo "ℹ️ No bucket specified. Keeping local copy."
fi

# Cleanup old local backups (keep last 7 days)
find $BACKUP_DIR -name "backup_*.gz" -mtime +7 -delete
