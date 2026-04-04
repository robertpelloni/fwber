# Smoke Report Drift Diff — Testing Notes

**Date:** 2026-04-04  
**Version:** 1.5.2

## Scope
This testing pass validates:
- `ops/hetzner/scripts/compare-smoke-reports.py`
- deploy integration updates in `ops/hetzner/scripts/deploy-backend.sh`

## Validation Performed
### Static validation
Executed:

```bash
bash -n ops/hetzner/scripts/deploy-backend.sh
```

### Comparison validation
Executed by generating two smoke reports and comparing them:

```bash
python3 ops/hetzner/scripts/compare-smoke-reports.py \
  --previous <report-a>/smoke-check-summary.json \
  --current <report-b>/smoke-check-summary.json \
  --json-out <report-b>/smoke-check-drift.json \
  --md-out <report-b>/smoke-check-drift.md
```

## Validated Outcomes
- drift JSON generated successfully
- drift Markdown generated successfully
- summary deltas were computed
- diagnostic drift was computed
- endpoint fingerprint drift and DNS drift sections were rendered

## Current Observed Behavior
In the compared local public-domain smoke runs:
- summary remained stable
- diagnostics remained stable
- no endpoint fingerprint drift was detected between the two sampled runs
- no DNS drift was detected between the two sampled runs

That is expected for two closely spaced runs against the same current public environment.
