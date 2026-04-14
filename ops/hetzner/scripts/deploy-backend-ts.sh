#!/usr/bin/env bash
set -euo pipefail

# Deployment script for the TypeScript backend stack on Hetzner.

REPO_ROOT="/var/www/fwber/repo"
BACKEND_DIR="$REPO_ROOT/fwber-backend-ts"
SUDO_BIN=""

if [ "$(id -u)" -ne 0 ]; then
  SUDO_BIN="sudo"
fi

cd "$REPO_ROOT"
git pull origin main

cd "$BACKEND_DIR"
npm install --production
npx prisma generate
npx prisma migrate deploy
npm run build

# Restart service via PM2
if command -v pm2 &> /dev/null; then
  pm2 restart fwber-backend-ts || pm2 start dist/index.js --name "fwber-backend-ts"
else
  echo "⚠️ PM2 not found, attempting systemd restart..."
  $SUDO_BIN systemctl restart fwber-backend-ts
fi

$SUDO_BIN systemctl reload nginx

echo "fwber TypeScript backend deployment complete."
