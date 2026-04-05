#!/usr/bin/env bash
set -euo pipefail

# Creates a dedicated restoration branch from the final pre-simplification
# snapshot so we can replay Hetzner/runtime hardening on top of the richer
# feature baseline without destabilizing main.

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
BASELINE_COMMIT="${BASELINE_COMMIT:-a636a53c3}"
BRANCH_NAME="${BRANCH_NAME:-restore/pre-simplification-hetzner}"
REMOTE_NAME="${REMOTE_NAME:-origin}"

cd "$REPO_ROOT"

echo "[restore] repo: $REPO_ROOT"
echo "[restore] baseline: $BASELINE_COMMIT"
echo "[restore] branch: $BRANCH_NAME"

git fetch "$REMOTE_NAME"

if git show-ref --verify --quiet "refs/heads/$BRANCH_NAME"; then
  echo "[restore] local branch already exists: $BRANCH_NAME"
else
  git branch "$BRANCH_NAME" "$BASELINE_COMMIT"
  echo "[restore] created local branch $BRANCH_NAME from $BASELINE_COMMIT"
fi

if git ls-remote --exit-code --heads "$REMOTE_NAME" "$BRANCH_NAME" >/dev/null 2>&1; then
  echo "[restore] remote branch already exists: $REMOTE_NAME/$BRANCH_NAME"
else
  git push "$REMOTE_NAME" "$BRANCH_NAME:$BRANCH_NAME"
  echo "[restore] pushed remote branch $REMOTE_NAME/$BRANCH_NAME"
fi

echo "[restore] done"
