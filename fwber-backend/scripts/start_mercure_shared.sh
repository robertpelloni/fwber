#!/bin/bash

# FWBer Mercure Startup Script for Shared Hosting (No Sudo)
# Usage: ./start_mercure_shared.sh

# 1. Load environment variables from .env
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
else
    echo "Error: .env file not found in current directory."
    exit 1
fi

# 2. Set CORS Allowed Origins explicitly if not set in .env
# This fixes the "Cross-Origin Request Blocked" error
if [ -z "$MERCURE_CORS_ALLOWED_ORIGINS" ]; then
    export MERCURE_CORS_ALLOWED_ORIGINS="https://fwber.me https://www.fwber.me"
    echo "Set default CORS origins: $MERCURE_CORS_ALLOWED_ORIGINS"
fi

# 3. Set other required Mercure variables if missing
# These should ideally be in your .env, but defaults help
# Use http:// to prevent Auto-HTTPS (which can cause issues on shared hosting)
export SERVER_NAME=${MERCURE_SERVER_NAME:-"http://:3001"} 
export MERCURE_PUBLISHER_JWT_KEY=${MERCURE_PUBLISHER_JWT_KEY}
export MERCURE_SUBSCRIBER_JWT_KEY=${MERCURE_SUBSCRIBER_JWT_KEY}

# Optimize for Shared Hosting (Low Memory)
# Aggressive memory limits for shared hosting environments
export GOMEMLIMIT=64MiB
export GOGC=20
echo "Memory limits set: GOMEMLIMIT=$GOMEMLIMIT, GOGC=$GOGC"

# Disable internal metrics to save memory
export MERCURE_METRICS_ENABLED=0

# 4. Check for Mercure binary
MERCURE_BIN="./mercure"
if [ ! -f "$MERCURE_BIN" ]; then
    echo "Mercure binary not found at $MERCURE_BIN"
    echo "Downloading Mercure binary..."
    
    # Use v0.21.4 (stable)
    DOWNLOAD_URL="https://github.com/dunglas/mercure/releases/download/v0.21.4/mercure_Linux_x86_64.tar.gz"
    
    if command -v wget &> /dev/null; then
        if ! wget -O mercure.tar.gz "$DOWNLOAD_URL"; then
            echo "Error: Download failed with wget."
            rm -f mercure.tar.gz
            exit 1
        fi
    elif command -v curl &> /dev/null; then
        if ! curl -L -o mercure.tar.gz "$DOWNLOAD_URL"; then
            echo "Error: Download failed with curl."
            rm -f mercure.tar.gz
            exit 1
        fi
    else
        echo "Error: Neither wget nor curl found. Cannot download Mercure."
        exit 1
    fi
    
    if [ -s "mercure.tar.gz" ]; then
        echo "Extracting Mercure..."
        if tar xzvf mercure.tar.gz; then
            rm mercure.tar.gz
            chmod +x mercure
            echo "Mercure installed successfully."
        else
            echo "Error: Extraction failed."
            rm mercure.tar.gz
            exit 1
        fi
    else
        echo "Error: Downloaded file is empty or missing."
        exit 1
    fi
fi

# 5. Kill existing Mercure processes
echo "Checking for existing Mercure processes..."
# Find PIDs of 'mercure run' but exclude this script and grep itself
PIDS=$(ps aux | grep "$MERCURE_BIN" | grep -v "grep" | grep -v "start_mercure_shared.sh" | awk '{print $2}')

if [ -n "$PIDS" ]; then
    echo "Found running Mercure processes: $PIDS"
    for PID in $PIDS; do
        echo "Killing process $PID..."
        kill $PID
    done
    echo "Waiting for processes to exit..."
    sleep 2
else
    echo "No existing Mercure processes found."
fi

# 6. Start Mercure
echo "Starting Mercure on $SERVER_NAME..."
echo "CORS Allowed Origins: $MERCURE_CORS_ALLOWED_ORIGINS"

# Run in background or foreground?
# For testing/manual start:
$MERCURE_BIN run

# For background (uncomment to use):
# nohup $MERCURE_BIN run > mercure.log 2>&1 &
# echo "Mercure started in background. PID: $!"
