# Repository Cleanup Summary (2025-11-15)

This document summarizes a conservative cleanup pass that consolidated docs, scripts, and test artifacts while leaving any potentially active application code (PHP pages, API endpoints, Laravel backend) untouched.

If something was moved unexpectedly, see the Revert section below.

## What changed

- Moved project cleanup reports to docs/archive/:
  - CLEANUP_COMPLETED.md → docs/archive/
  - CLEANUP_MERGED.md → docs/archive/
  - CLEANUP_PLAN.md → docs/archive/
  - CLEANUP_SUMMARY.md → docs/archive/

- Moved a general doc:
  - WARP.md → docs/WARP.md

- Consolidated scripts:
  - fix-database-quick.ps1 → scripts/fix-database-quick.ps1
  - cleanup-project.sh → scripts/cleanup-project.sh
  - final_mcp_verification.ps1 → tests/scripts/powershell/final_mcp_verification.ps1
  - drop-events-table.php → scripts/drop-events-table.php
  - fix-database-user.php → scripts/fix-database-user.php

- Consolidated test prompts and outputs into test-results/:
  - codex_mcp_test_prompt.txt
  - final_test_prompt.txt
  - test_prompt.txt
  - mcp_test_output.txt
  - mcp_test_error.txt
  - test_output.txt
  - test_error.txt

- Archived legacy flat PHP application files to legacy-php/:
  - Authentication: signin.php, signout.php, forgot-password.php, forgotpassword.php, verify-email.php, verify.php
  - Admin: admin-dashboard.php, admin-login.php
  - Venue: venue-dashboard.php, venue-login.php
  - Profile/Settings: edit-profile.php, editprofile.php, profile.php, profile-form.php, settings.php
  - Matching/Photos: matches.php, location-matches.php, realtime-matches.php, realtime-demo.php, manage-pics.php, managepics.php
  - Public pages: contact.php, privacy.php, tos.php, unsubscribe.php, join.php, index.php, index-mvp.php
  - Utility/Engine classes: ai-matching-engine.php, avatar-generator.php, EnhancedMatchingEngine.php, MatchingEngine.php, PhotoManager.php, ProfileManager.php, recaptchalib.php
  - Config/Security: config-template.php, secure-config.php, security-manager.php
  - Index/utility files: f.php, h.php, l.php, head.php
  - SQL setup scripts: setup-admin-tables.sql, setup-database.sql, setup-venue-tables.sql

## What we intentionally did NOT touch

- Laravel backend under fwber-backend/ (modern application stack)
- Frontend code under fwber-frontend/ (Next.js/React)
- API endpoints under api/ (may still be referenced)
- Deployment/docker files and top-level project metadata (README.md, LICENSE, PRIVACY.md, TERMS.md, AGENTS.md, CONTRIBUTING.md)
- Static assets (images/, js/, avatars/, styles.css, service-worker.js, manifest files, favicons)
- Infrastructure directories (database/, db/, logs/, ssl/, vendor/, node_modules/, archive/, tools/)

The Laravel backend (fwber-backend/) is the modern replacement for these legacy flat PHP files. These files are preserved in legacy-php/ for reference but are no longer needed for active development.

## Rationale

- Reduce root clutter and make it easier to find docs, scripts, and test artifacts
- Keep active application code in place to avoid breaking running environments
- Prefer moves over deletions so content remains available for review

## Revert instructions

To move a file back to its original location, use `git` (preferred) or manually relocate the file:

### Reverting documentation moves
```bash
git mv docs/archive/CLEANUP_SUMMARY.md ./CLEANUP_SUMMARY.md
git mv docs/WARP.md ./WARP.md
```

### Reverting script moves
```bash
git mv scripts/fix-database-quick.ps1 ./fix-database-quick.ps1
git mv scripts/cleanup-project.sh ./cleanup-project.sh
git mv tests/scripts/powershell/final_mcp_verification.ps1 ./final_mcp_verification.ps1
```

### Reverting legacy PHP moves (example)
```bash
git mv legacy-php/index.php ./index.php
git mv legacy-php/signin.php ./signin.php
git mv legacy-php/admin-dashboard.php ./admin-dashboard.php
# ... repeat for any other files you need to restore
```

### Reverting test artifact moves
```bash
git mv test-results/codex_mcp_test_prompt.txt ./codex_mcp_test_prompt.txt
git mv test-results/mcp_test_output.txt ./mcp_test_output.txt
```

If you are not using Git for this change, you can drag the file from its new folder back to the repository root.

## Next steps (optional)

- If legacy PHP files are no longer needed, consider removing legacy-php/ entirely after confirming the Laravel backend fully replaces all functionality
- Update any external documentation or deployment guides that reference old flat PHP file paths
- Add redirect rules in .htaccess or nginx.conf if legacy URLs need to point to new Laravel routes
- Normalize deployment scripts under tools/deployment/ and reference them from docs
- Add a CI job to keep `test-results/` clean or auto-archive artifacts per run
- Merge duplicated scripts located in both root and tests/scripts/powershell if any remain
