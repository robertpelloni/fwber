# Smoke Report Drift Diff — Implementation Notes

**Date:** 2026-04-04  
**Version:** 1.5.2

## Problem
By v1.5.1 the smoke-check system could capture:
- raw pass/fail results
- diagnostics
- endpoint fingerprints
- DNS mappings

But there was still no automated way to compare one deploy's smoke evidence against the previous one.

That meant operators still had to manually diff two JSON reports to answer:
- what changed between deploys?
- did any diagnostics resolve?
- did any endpoint fingerprints change?
- did DNS resolution drift between runs?

## Implementation Summary
Added:
- `ops/hetzner/scripts/compare-smoke-reports.py`

This script compares two `smoke-check-summary.json` files and generates:
- `smoke-check-drift.json`
- `smoke-check-drift.md`

## What It Compares
### Summary delta
- passes
- warnings
- failures
- overall status

### Diagnostic drift
- new diagnostics
- resolved diagnostics
- unchanged diagnostics

### Endpoint fingerprint drift
Per endpoint label, it compares:
- HTTP code
- remote IP
- server header
- content type
- location header
- effective URL

### DNS drift
Per DNS record label, it compares:
- host
- resolved addresses

## Deploy Integration
Updated:
- `ops/hetzner/scripts/deploy-backend.sh`

When smoke checks are enabled and a previous report exists, the deploy script now attempts to create drift reports in the current report directory.

Python interpreter selection:
- `FWBER_PYTHON_BIN` if supplied
- otherwise `python3`
- fallback to `python` when needed

## Why This Matters
This turns the deploy evidence system into a historical comparison tool instead of a sequence of isolated reports.
