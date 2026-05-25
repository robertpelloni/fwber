#!/bin/bash

# Configuration
BASE_URL="${APP_URL:-http://localhost:8000}"
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo "Starting Health Checks against $BASE_URL..."

# Function to check endpoint
check_endpoint() {
    local endpoint=$1
    local name=$2
    
    echo -n "Checking $name ($endpoint)... "
    
    response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL$endpoint")
    
    if [ "$response" == "200" ]; then
        echo -e "${GREEN}OK (200)${NC}"
        return 0
    else
        echo -e "${RED}FAILED ($response)${NC}"
        return 1
    fi
}

# Run Checks
failures=0

check_endpoint "/api/health" "General Health" || ((failures++))
check_endpoint "/api/health/liveness" "Liveness Probe" || ((failures++))
check_endpoint "/api/health/readiness" "Readiness Probe" || ((failures++))

# Summary
echo "----------------------------------------"
if [ $failures -eq 0 ]; then
    echo -e "${GREEN}All checks passed! System is healthy.${NC}"
    exit 0
else
    echo -e "${RED}$failures check(s) failed.${NC}"
    exit 1
fi
