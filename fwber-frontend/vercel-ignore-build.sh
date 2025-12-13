#!/bin/bash

# FWBer Vercel Ignored Build Step Script
# Usage: Set "Ignored Build Step" in Vercel to: bash vercel-ignore-build.sh

# Determine the path to check
# If we are running from repo root, check fwber-frontend
# If we are running from fwber-frontend (Vercel Root Directory), check .
TARGET_PATH="."
if [ -d "fwber-frontend" ]; then
    TARGET_PATH="fwber-frontend"
fi

echo "Checking for changes in: $TARGET_PATH"

# Check if we have enough history
if ! git rev-parse --verify HEAD^ >/dev/null 2>&1; then
    echo "Warning: No previous commit found (shallow clone?). Proceeding with build."
    exit 1 # Build
fi

# Check for changes
# git diff --quiet returns 0 if NO changes (Skip Build)
# git diff --quiet returns 1 if CHANGES (Proceed Build)
if git diff --quiet HEAD^ HEAD -- "$TARGET_PATH"; then
    echo "No changes detected in $TARGET_PATH. Skipping build."
    exit 0
else
    echo "Changes detected in $TARGET_PATH. Proceeding with build."
    exit 1
fi
