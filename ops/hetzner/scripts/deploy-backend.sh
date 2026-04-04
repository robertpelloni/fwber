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
COMPARE_SMOKE_SCRIPT="$REPO_ROOT/ops/hetzner/scripts/compare-smoke-reports.py"
REPORT_DIR_ROOT="${FWBER_DEPLOY_REPORT_DIR:-$REPO_ROOT/logs/deploy-reports}"
PYTHON_BIN="${FWBER_PYTHON_BIN:-python3}"

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
  mkdir -p "$REPORT_DIR_ROOT"
  PREVIOUS_REPORT_JSON="$(find "$REPORT_DIR_ROOT" -mindepth 2 -maxdepth 2 -type f -name 'smoke-check-summary.json' | sort | tail -n 1 || true)"

  REPORT_DIR="$REPORT_DIR_ROOT/$(date -u +%Y%m%dT%H%M%SZ)"
  mkdir -p "$REPORT_DIR"

  FWBER_BACKEND_DIR="$BACKEND_DIR" \
  FWBER_REPORT_DIR="$REPORT_DIR" \
  "$SMOKE_CHECK_SCRIPT"

  CURRENT_REPORT_JSON="$REPORT_DIR/smoke-check-summary.json"
  if ! command -v "$PYTHON_BIN" >/dev/null 2>&1 && command -v python >/dev/null 2>&1; then
    PYTHON_BIN="python"
  fi

  if [ -n "$PREVIOUS_REPORT_JSON" ] && [ -f "$PREVIOUS_REPORT_JSON" ] && [ -f "$CURRENT_REPORT_JSON" ] && [ -f "$COMPARE_SMOKE_SCRIPT" ] && command -v "$PYTHON_BIN" >/dev/null 2>&1; then
    "$PYTHON_BIN" "$COMPARE_SMOKE_SCRIPT" \
      --previous "$PREVIOUS_REPORT_JSON" \
      --current "$CURRENT_REPORT_JSON" \
      --json-out "$REPORT_DIR/smoke-check-drift.json" \
      --md-out "$REPORT_DIR/smoke-check-drift.md"

    echo "Smoke-check drift reports written to $REPORT_DIR"
  else
    echo "Smoke-check drift comparison skipped because no previous report was available or compare-smoke-reports.py was missing."
  fi

  echo "Smoke-check reports written to $REPORT_DIR"
else
  echo "Smoke check skipped. Set FWBER_RUN_SMOKE_CHECK=1 to run ops/hetzner/scripts/smoke-check.sh after deploy."
fi

echo "fwber backend deployment complete."
