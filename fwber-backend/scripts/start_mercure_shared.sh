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

# Configure transport to use local file instead of default system path
# This fixes "invalid transport: open ...: no such file or directory"
# Also inject CORS origins explicitly for Caddyfile
export MERCURE_EXTRA_DIRECTIVES="cors_origins $MERCURE_CORS_ALLOWED_ORIGINS
transport_url bolt://$(pwd)/mercure.db"

# Optimize for Shared Hosting (Low Memory)
# Aggressive memory limits for shared hosting environments
export GOMEMLIMIT=32MiB
export GOGC=10
# Limit threads to prevent resource exhaustion on shared CPUs
export GOMAXPROCS=1
echo "Memory limits set: GOMEMLIMIT=$GOMEMLIMIT, GOGC=$GOGC, GOMAXPROCS=$GOMAXPROCS"

# Disable internal metrics and UI to save memory
export MERCURE_METRICS_ENABLED=0
export MERCURE_DEMO=0
export MERCURE_SWAGGER_UI=0

# Debugging: Print system limits
echo "System Limits (ulimit -a):"
ulimit -a
echo "--------------------------------"

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

# Verify binary works
echo "Verifying Mercure binary..."
echo "File info:"
file $MERCURE_BIN || echo "file command not found"
echo "LDD info:"
ldd $MERCURE_BIN || echo "ldd command not found"

echo "Attempting to run version check..."
$MERCURE_BIN --version || { 
    echo "Error: Mercure binary failed to run."
    echo "Trying to download an older, potentially more compatible version (v0.11.0)..."
    
    rm -f $MERCURE_BIN
    DOWNLOAD_URL="https://github.com/dunglas/mercure/releases/download/v0.11.0/mercure_0.11.0_Linux_x86_64.tar.gz"
    
    if command -v wget &> /dev/null; then
        wget -O mercure.tar.gz "$DOWNLOAD_URL"
    else
        curl -L -o mercure.tar.gz "$DOWNLOAD_URL"
    fi
    
    tar xzvf mercure.tar.gz
    rm mercure.tar.gz
    chmod +x mercure
    
    echo "Retrying with v0.11.0..."
    # v0.11.0 uses 'caddy run' syntax or just 'mercure' depending on build
    # The error "[ERROR] first argument must be a subcommand; see 'caddy help'" suggests it's based on Caddy v2
    # and expects a command.
    
    # Try running with 'run' command for version check if --version fails
    $MERCURE_BIN version || $MERCURE_BIN --version || echo "Version check failed, but binary might be runnable."
}

# 5. Kill existing Mercure processes
echo "Checking for existing Mercure processes..."
# Find PIDs of 'mercure' but exclude this script and grep itself
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

# Run in background using nohup
nohup $MERCURE_BIN run --config Caddyfile --adapter caddyfile > mercure.log 2>&1 &
echo "Mercure started in background. PID: $!"
echo "Logs available in mercure.log"
