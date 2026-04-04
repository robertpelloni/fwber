#!/usr/bin/env bash
set -euo pipefail

# Zero-surprise deployment script for the Hetzner-hosted backend stack.
#
# It updates code, installs optimized PHP dependencies, runs safe migrations,
# rebuilds the Rust geo service, and restarts the managed runtime services.

REPO_ROOT="/var/www/fwber/repo"
BACKEND_DIR="$REPO_ROOT/fwber-backend"
GEO_DIR="$REPO_ROOT/fwber-geo"
SMOKE_CHECK_SCRIPT="$REPO_ROOT/ops/hetzner/scripts/smoke-check.sh"

cd "$REPO_ROOT"
git pull origin main

cd "$BACKEND_DIR"
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan deploy:verify

if [ -d "$GEO_DIR" ]; then
  cd "$GEO_DIR"
  cargo build --release
fi

systemctl restart fwber-queue
systemctl restart fwber-reverb
systemctl restart fwber-geo
systemctl reload nginx

if [ "${FWBER_RUN_SMOKE_CHECK:-0}" = "1" ] && [ -x "$SMOKE_CHECK_SCRIPT" ]; then
  FWBER_BACKEND_DIR="$BACKEND_DIR" "$SMOKE_CHECK_SCRIPT"
else
  echo "Smoke check skipped. Set FWBER_RUN_SMOKE_CHECK=1 to run ops/hetzner/scripts/smoke-check.sh after deploy."
fi

echo "fwber backend deployment complete."
