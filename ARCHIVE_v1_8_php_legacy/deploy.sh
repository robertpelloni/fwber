#!/usr/bin/env bash
set -e

#############################################################################
# fwber Next.js Frontend Deployment Script
# 
# Purpose: Automated deployment with validation, build, and restart
# Usage: ./deploy.sh [--env=production] [--branch=main]
#
# Author: AI-generated deployment automation
# Date: 2025-11-19
#############################################################################

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
ENVIRONMENT="production"
BRANCH="main"
DRY_RUN=false

# Parse command line arguments
for arg in "$@"; do
    case $arg in
        --env=*)
            ENVIRONMENT="${arg#*=}"
            shift
            ;;
        --branch=*)
            BRANCH="${arg#*=}"
            shift
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --help)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --env=ENV           Set environment (default: production)"
            echo "  --branch=BRANCH     Git branch to deploy (default: main)"
            echo "  --dry-run          Show what would be done without executing"
            echo "  --help             Show this help message"
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown argument: $arg${NC}"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

#############################################################################
# Helper Functions
#############################################################################

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_command() {
    if ! command -v $1 &> /dev/null; then
        log_error "Required command '$1' not found. Please install it."
        exit 1
    fi
}

run_or_dry() {
    if [ "$DRY_RUN" = true ]; then
        log_info "[DRY RUN] Would execute: $@"
    else
        "$@"
    fi
}

#############################################################################
# Pre-Deployment Checks
#############################################################################

log_info "=========================================="
log_info "fwber Frontend Deployment Script"
log_info "Environment: $ENVIRONMENT"
log_info "Branch: $BRANCH"
log_info "=========================================="
echo ""

# Check required commands
log_info "Checking required commands..."

# Try to load NVM if present
export NVM_DIR="$HOME/.nvm"
if [ -s "$NVM_DIR/nvm.sh" ]; then
    . "$NVM_DIR/nvm.sh"
elif [ -s "/usr/local/nvm/nvm.sh" ]; then
    . "/usr/local/nvm/nvm.sh"
fi

check_command node
check_command npm
check_command git
log_success "All required commands found"
echo ""

# Check Node version (require 18+)
NODE_VERSION=$(node -v)
NODE_MAJOR=$(echo $NODE_VERSION | cut -d. -f1 | sed 's/v//')

log_info "Node version: $NODE_VERSION"
if [ "$NODE_MAJOR" -lt 18 ]; then
    log_error "Node 18 or higher is required. Current version: $NODE_VERSION"
    exit 1
fi
log_success "Node version check passed"
echo ""

# Check if .env.local exists
if [ ! -f .env.local ]; then
    log_warning ".env.local file not found. Ensure environment variables are set."
fi
echo ""

#############################################################################
# Git Operations
#############################################################################

log_info "Updating code from Git repository..."

# Navigate to repo root if we are in fwber-frontend
if [ -d "../fwber-frontend" ] && [ -d "../.git" ]; then
    cd ..
    log_info "Changed directory to repository root: $PWD"
fi

run_or_dry git fetch origin
run_or_dry git checkout $BRANCH
run_or_dry git pull origin $BRANCH
log_success "Code updated to branch: $BRANCH"

# Navigate back to frontend directory if we are in root
if [ -d "fwber-frontend" ]; then
    cd fwber-frontend
    log_info "Changed directory to frontend: $PWD"
fi

echo ""

# Get current commit hash
COMMIT_HASH=$(git rev-parse --short HEAD)
log_info "Deployed commit: $COMMIT_HASH"
echo ""

#############################################################################
# Install Dependencies
#############################################################################

log_info "Installing dependencies..."
run_or_dry npm ci
log_success "Dependencies installed"
echo ""

#############################################################################
# Build Application
#############################################################################

log_info "Building application..."
run_or_dry npm run build
log_success "Application built successfully"
echo ""

#############################################################################
# Restart Application (PM2)
#############################################################################

log_info "Restarting application..."
if command -v pm2 &> /dev/null; then
    if [ "$DRY_RUN" = false ]; then
        pm2 reload fwber-frontend || pm2 start npm --name "fwber-frontend" -- start
        log_success "Application restarted via PM2"
    else
        log_info "[DRY RUN] Would restart application via PM2"
    fi
else
    log_warning "PM2 not found. Please restart the application manually."
fi
echo ""

#############################################################################
# Post-Deployment Summary
#############################################################################

echo ""
log_success "=========================================="
log_success "Deployment completed successfully!"
log_success "=========================================="
echo ""
log_info "Deployment Summary:"
log_info "  Environment: $ENVIRONMENT"
log_info "  Branch: $BRANCH"
log_info "  Commit: $COMMIT_HASH"
log_info "  Time: $(date)"
echo ""

if [ "$DRY_RUN" = true ]; then
    log_warning "This was a DRY RUN. No changes were made."
    echo ""
fi

log_info "Next steps:"
log_info "  1. Verify application is accessible"
log_info "  2. Check logs: pm2 logs fwber-frontend"
echo ""

exit 0
