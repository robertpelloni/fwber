#!/bin/bash

# Load environment variables
if [ -f ../.env ]; then
    export $(cat ../.env | grep -v '#' | awk '/=/ {print $1}')
fi

BACKUP_DIR="../storage/backups"

if [[ "$*" == *"--latest"* ]]; then
    # Find latest backup
    LATEST_BACKUP=$(ls -t $BACKUP_DIR/*.sql* | head -n 1)
    if [ -z "$LATEST_BACKUP" ]; then
        echo "No backups found in $BACKUP_DIR"
        exit 1
    fi
    BACKUP_FILE=$LATEST_BACKUP
else
    if [ -z "$1" ]; then
        echo "Usage: ./restore_database.sh <backup_file> or ./restore_database.sh --latest"
        exit 1
    fi
    BACKUP_FILE=$1
fi

echo "Restoring from: $BACKUP_FILE"

# Decompress if needed
if [[ "$BACKUP_FILE" == *.gz ]]; then
    gunzip -c $BACKUP_FILE | mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE
else
    mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE < $BACKUP_FILE
fi

if [ $? -eq 0 ]; then
    echo "Restore successful."
else
    echo "Restore failed."
    exit 1
fi
