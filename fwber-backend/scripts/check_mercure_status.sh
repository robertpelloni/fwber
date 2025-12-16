#!/bin/bash

# FWBer Mercure Status Check & Auto-Restart
# Usage: Add to crontab to run every minute:
# * * * * * /path/to/fwber-backend/scripts/check_mercure_status.sh

# Define paths - Adjust these if your path differs
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
MERCURE_BIN="$SCRIPT_DIR/../mercure"
START_SCRIPT="$SCRIPT_DIR/start_mercure_shared.sh"
LOG_FILE="$SCRIPT_DIR/../mercure_monitor.log"

# Check if Mercure is running
if pgrep -f "$MERCURE_BIN" > /dev/null; then
    # It is running, do nothing
    exit 0
else
    # It is NOT running, log and restart
    echo "[$(date)] Mercure is down. Attempting restart..." >> "$LOG_FILE"
    
    if [ -f "$START_SCRIPT" ]; then
        cd "$SCRIPT_DIR/.."
        "$START_SCRIPT" >> "$LOG_FILE" 2>&1
        echo "[$(date)] Restart command executed." >> "$LOG_FILE"
    else
        echo "[$(date)] Error: Start script not found at $START_SCRIPT" >> "$LOG_FILE"
    fi
fi
