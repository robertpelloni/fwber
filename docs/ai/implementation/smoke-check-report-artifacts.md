# Smoke Check Report Artifacts — Implementation Notes

**Date:** 2026-04-04  
**Version:** 1.4.8

## Problem
`ops/hetzner/scripts/smoke-check.sh` already validated the public post-deploy contract, but its output lived only in the terminal.

That created three gaps:
- no durable artifact to attach to release notes or cutover evidence
- no easy way to compare one smoke run against another
- no preserved proof of what exactly failed during live validation

## Implementation Summary
### 1. Report artifact support in `smoke-check.sh`
Added optional env-driven output paths:
- `FWBER_REPORT_DIR`
- `FWBER_REPORT_JSON_PATH`
- `FWBER_REPORT_MD_PATH`

When enabled, the script now writes:
- `smoke-check-summary.json`
- `smoke-check-summary.md`

### 2. Case-level result recording
The script now stores each pass, warning, and failure as a structured case entry before printing it.

That lets one run generate both:
- human-readable console output
- machine-readable archival output

### 3. Deploy-script integration
Updated `ops/hetzner/scripts/deploy-backend.sh` so that when `FWBER_RUN_SMOKE_CHECK=1` is enabled it also creates a timestamped report directory under:

```bash
logs/deploy-reports/<timestamp>/
```

The root can be overridden with:

```bash
FWBER_DEPLOY_REPORT_DIR=/custom/path
```

## Important Fix
While validating the feature, the first live report-writing run exposed a shell edge case: `printf` calls used dash-prefixed format strings in the Markdown writer, which some shells interpreted as options.

This was corrected by switching those calls to `printf -- ...`, ensuring Markdown artifact generation still works when the smoke run itself fails.

## Design Rationale
- JSON artifacts are useful for future CI, Slack, or parsing workflows.
- Markdown artifacts are useful for humans, release notes, and handoff docs.
- Timestamped directories are safer than overwriting one global report file during repeated redeploys.
