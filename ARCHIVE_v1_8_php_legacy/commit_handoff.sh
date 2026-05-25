#!/bin/bash

# Commit Handoff Script
# This script stages and commits the documentation and cleanup changes made during the handoff phase.

# 1. Stage Documentation Changes
git add fwber-backend/storage/api-docs/api-docs.json
git add fwber-backend/docs/README.md
git add fwber-backend/docs/HANDOFF_STATUS_2025-01-21.md
git add fwber-backend/CHANGELOG.md
git add .github/workflows/generate-api-docs.yml

# 2. Stage Project Cleanup (Archive)
git add archive/
git add fwber-backend/

# 3. Commit
git commit -m "chore: Finalize API documentation handoff and project cleanup

- Regenerated OpenAPI specs with new AI, Real-time, and Security endpoints.
- Consolidated legacy files into archive/.
- Added documentation index and status report.
- Added CI workflow for auto-generating API docs."

echo "Handoff changes committed successfully."
