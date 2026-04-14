#!/bin/bash

# Load environment variables
if [ -f ../.env ]; then
    export $(cat ../.env | grep -v '#' | awk '/=/ {print $1}')
fi

TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="../storage/backups"
FILENAME="backup_${TIMESTAMP}.sql"
FILEPATH="${BACKUP_DIR}/${FILENAME}"

mkdir -p $BACKUP_DIR

echo "Starting backup for database: $DB_DATABASE"

# Dump database
# Use --no-tablespaces for compatibility with some cloud providers (like RDS)
# Include port if specified
DB_PORT=${DB_PORT:-3306}

mysqldump --no-tablespaces -h $DB_HOST -P $DB_PORT -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE > $FILEPATH

if [ $? -eq 0 ]; then
    echo "Backup created successfully: $FILEPATH"
    
    # Compress if requested
    if [[ "$*" == *"--compress"* ]]; then
        gzip $FILEPATH
        echo "Backup compressed: ${FILEPATH}.gz"
        FILEPATH="${FILEPATH}.gz"
    fi

    # Upload to S3 if requested
    if [[ "$*" == *"--upload-s3"* ]]; then
        if [ -z "$BACKUP_S3_BUCKET" ]; then
            echo "Error: BACKUP_S3_BUCKET not set in .env"
        else
            echo "Uploading to S3..."
            aws s3 cp $FILEPATH s3://${BACKUP_S3_BUCKET}/${BACKUP_S3_PREFIX}/$(basename $FILEPATH)
            if [ $? -eq 0 ]; then
                echo "Upload successful."
            else
                echo "Upload failed."
            fi
        fi
    fi
else
    echo "Backup failed!"
    exit 1
fi
