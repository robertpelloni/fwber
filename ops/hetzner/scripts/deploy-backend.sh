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
PUBLISH_SMOKE_SCRIPT="$REPO_ROOT/ops/hetzner/scripts/publish-smoke-report.py"
REPORT_DIR_ROOT="${FWBER_DEPLOY_REPORT_DIR:-$REPO_ROOT/logs/deploy-reports}"
PYTHON_BIN="${FWBER_PYTHON_BIN:-python3}"
SUDO_BIN=""

# GitHub Actions and other non-login SSH executions do not reliably source the deploy user's
# shell profile, which means Rust installed via rustup can disappear from PATH even though the
# server is correctly provisioned. Prefer the rustup toolchain explicitly when present so geo
# builds stay consistent across manual SSH sessions and CI-triggered deployments.
if [ -f "$HOME/.cargo/env" ]; then
  # shellcheck disable=SC1090
  . "$HOME/.cargo/env"
fi
if [ -d "$HOME/.cargo/bin" ]; then
  export PATH="$HOME/.cargo/bin:$PATH"
fi

if [ "$(id -u)" -ne 0 ]; then
  SUDO_BIN="sudo"
fi

cd "$REPO_ROOT"
git pull origin main

cd "$BACKEND_DIR"
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan deploy:verify

# Daily log files can be created by the web runtime between deploys. Make a best-effort
# attempt to keep existing log files group-writable so deploy-user artisan commands do not
# fail the next day when Monolog rotates into a file owned by the PHP-FPM user.
if [ -d "$BACKEND_DIR/storage/logs" ]; then
  find "$BACKEND_DIR/storage/logs" -maxdepth 1 -type f -name '*.log' -exec chmod ug+rw {} + 2>/dev/null || true
fi

if [ -d "$GEO_DIR" ]; then
  cd "$GEO_DIR"
  cargo build --release
fi

$SUDO_BIN systemctl restart fwber-queue
$SUDO_BIN systemctl restart fwber-reverb
$SUDO_BIN systemctl restart fwber-geo
$SUDO_BIN systemctl reload nginx

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

  if [ -f "$CURRENT_REPORT_JSON" ] && [ -f "$PUBLISH_SMOKE_SCRIPT" ] && command -v "$PYTHON_BIN" >/dev/null 2>&1; then
    PUBLISH_ARGS=(
      --summary-json "$CURRENT_REPORT_JSON"
      --json-out "$REPORT_DIR/smoke-check-notification.json"
      --md-out "$REPORT_DIR/smoke-check-notification.md"
    )

    if [ -f "$REPORT_DIR/smoke-check-drift.json" ]; then
      PUBLISH_ARGS+=(--drift-json "$REPORT_DIR/smoke-check-drift.json")
    fi

    if [ -n "${FWBER_SMOKE_NOTIFY_WEBHOOK_URL:-}" ]; then
      PUBLISH_ARGS+=(--webhook-url "$FWBER_SMOKE_NOTIFY_WEBHOOK_URL")
    fi

    "$PYTHON_BIN" "$PUBLISH_SMOKE_SCRIPT" "${PUBLISH_ARGS[@]}"
    echo "Smoke-check notification artifacts written to $REPORT_DIR"
  else
    echo "Smoke-check notification publishing skipped because publish-smoke-report.py or Python was unavailable."
  fi

  echo "Smoke-check reports written to $REPORT_DIR"
else
  echo "Smoke check skipped. Set FWBER_RUN_SMOKE_CHECK=1 to run ops/hetzner/scripts/smoke-check.sh after deploy."
fi

echo "fwber backend deployment complete."
