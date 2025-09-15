#!/bin/bash
# FWBer.me Development Server Testing Script
# This script starts and tests the development servers

set -e

echo "========================================"
echo "FWBer.me Development Server Testing"
echo "========================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

success() {
    echo -e "${GREEN}✓ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

error() {
    echo -e "${RED}✗ $1${NC}"
}

info() {
    echo -e "ℹ $1"
}

# Cleanup function
cleanup() {
    echo "Cleaning up processes..."
    if [ ! -z "$LARAVEL_PID" ]; then
        kill $LARAVEL_PID 2>/dev/null || true
    fi
    if [ ! -z "$NEXTJS_PID" ]; then
        kill $NEXTJS_PID 2>/dev/null || true
    fi
}

# Set trap to cleanup on exit
trap cleanup EXIT

echo ""
echo "==================================="
echo "1. Starting Laravel Backend"
echo "==================================="

cd fwber-backend

info "Starting Laravel development server..."
php artisan serve --port=8000 > /tmp/laravel_dev.log 2>&1 &
LARAVEL_PID=$!

# Wait for Laravel to start
sleep 5

if curl -s http://localhost:8000 > /dev/null; then
    success "Laravel backend is running on http://localhost:8000"
else
    error "Laravel backend failed to start"
    cat /tmp/laravel_dev.log
    exit 1
fi

# Test API endpoints
info "Testing API endpoints..."
api_status=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/api)
if [ "$api_status" = "404" ] || [ "$api_status" = "200" ]; then
    success "API endpoint responding (HTTP $api_status)"
else
    warning "API endpoint returned HTTP $api_status"
fi

cd ..

echo ""
echo "==================================="
echo "2. Starting Next.js Frontend"
echo "==================================="

cd fwber-frontend

info "Starting Next.js development server..."
npm run dev > /tmp/nextjs_dev.log 2>&1 &
NEXTJS_PID=$!

# Wait for Next.js to start
sleep 10

if curl -s http://localhost:3000 > /dev/null; then
    success "Next.js frontend is running on http://localhost:3000"
else
    warning "Next.js frontend may still be starting up"
    # Show recent logs
    tail -10 /tmp/nextjs_dev.log
fi

cd ..

echo ""
echo "==================================="
echo "3. Testing Basic Functionality"
echo "==================================="

info "Testing Laravel health check..."
if curl -s http://localhost:8000 | grep -q "Laravel"; then
    success "Laravel is serving content"
else
    warning "Laravel content check inconclusive"
fi

info "Testing Next.js page load..."
frontend_status=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:3000)
if [ "$frontend_status" = "200" ]; then
    success "Next.js frontend is serving content (HTTP 200)"
else
    warning "Next.js frontend returned HTTP $frontend_status"
fi

echo ""
echo "==================================="
echo "4. Development Environment Status"
echo "==================================="

info "Checking process status..."
if ps -p $LARAVEL_PID > /dev/null 2>&1; then
    success "Laravel process is running (PID: $LARAVEL_PID)"
else
    error "Laravel process has stopped"
fi

if ps -p $NEXTJS_PID > /dev/null 2>&1; then
    success "Next.js process is running (PID: $NEXTJS_PID)"
else
    warning "Next.js process may have stopped or still starting"
fi

echo ""
echo "==================================="
echo "Development Server Summary"
echo "==================================="

echo "🚀 FWBer.me Development Environment Ready!"
echo ""
echo "Services running:"
echo "• Laravel Backend: http://localhost:8000 (PID: $LARAVEL_PID)"
echo "• Next.js Frontend: http://localhost:3000 (PID: $NEXTJS_PID)"
echo ""
echo "To stop servers:"
echo "  kill $LARAVEL_PID $NEXTJS_PID"
echo ""
echo "Log files:"
echo "  Laravel: /tmp/laravel_dev.log"
echo "  Next.js: /tmp/nextjs_dev.log"
echo ""
echo "Press Ctrl+C to stop all servers and exit."

# Keep script running until interrupted
while true; do
    sleep 30
    
    # Check if processes are still running
    if ! ps -p $LARAVEL_PID > /dev/null 2>&1; then
        error "Laravel process has stopped unexpectedly"
        break
    fi
    
    if ! ps -p $NEXTJS_PID > /dev/null 2>&1; then
        warning "Next.js process has stopped"
    fi
done