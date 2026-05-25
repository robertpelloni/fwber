# Smoke Report Notification Publisher — Implementation Notes

**Date:** 2026-04-04  
**Version:** 1.5.3

## Problem
By v1.5.2 the smoke-check system could generate:
- summary artifacts
- diagnostics
- endpoint fingerprints
- DNS appendix data
- drift diffs

But there was still no concise publishable summary for operators, chat tools, or webhook-based notification systems.

## Implementation Summary
Added:
- `ops/hetzner/scripts/publish-smoke-report.py`

This script consumes:
- the current `smoke-check-summary.json`
- optionally the current `smoke-check-drift.json`

And generates:
- `smoke-check-notification.json`
- `smoke-check-notification.md`

It can also optionally POST the resulting payload to a webhook.

## Payload Shape
The publisher produces a compact notification payload containing:
- markdown `text`
- `report_dir`
- overall status
- summary counters
- top diagnostics
- drift summary when available

This keeps the message compact while still linking back to the full report directory.

## Deploy Integration
Updated:
- `ops/hetzner/scripts/deploy-backend.sh`

When smoke checks run and Python is available, the deploy script now also generates notification artifacts in the current report directory.

Optional webhook publishing is enabled with:
- `FWBER_SMOKE_NOTIFY_WEBHOOK_URL`

Optional Python binary override:
- `FWBER_PYTHON_BIN`

## Why This Matters
This closes the gap between rich evidence artifacts and human notification workflows. Operators can now consume a short, publishable deploy summary without manually condensing the larger smoke and drift reports.
