#!/usr/bin/env bash
set -euo pipefail

# Zero-surprise deployment script for the Hetzner-hosted backend stack.
#
# It updates code, installs optimized PHP dependencies, runs safe migrations,
# rebuilds the Rust geo service, and restarts the managed runtime services.

REPO_ROOT="/var/www/fwber/repo"
BACKEND_DIR="$REPO_ROOT/fwber-backend"
GEO_DIR="$REPO_ROOT/fwber-geo"

cd "$REPO_ROOT"
git pull origin main

cd "$BACKEND_DIR"
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize

if [ -d "$GEO_DIR" ]; then
  cd "$GEO_DIR"
  cargo build --release
fi

systemctl restart fwber-queue
systemctl restart fwber-reverb
systemctl restart fwber-geo
systemctl reload nginx

echo "fwber backend deployment complete."
