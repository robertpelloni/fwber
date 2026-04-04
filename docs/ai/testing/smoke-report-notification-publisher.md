# Smoke Report Notification Publisher — Testing Notes

**Date:** 2026-04-04  
**Version:** 1.5.3

## Scope
This testing pass validates:
- `ops/hetzner/scripts/publish-smoke-report.py`
- deploy integration updates in `ops/hetzner/scripts/deploy-backend.sh`

## Validation Performed
### Static validation
Executed:

```bash
bash -n ops/hetzner/scripts/deploy-backend.sh
python3 ops/hetzner/scripts/publish-smoke-report.py --help
```

### End-to-end local artifact validation
Executed by generating smoke reports, generating a drift report, then running the publisher:

```bash
python3 ops/hetzner/scripts/publish-smoke-report.py \
  --summary-json <report>/smoke-check-summary.json \
  --drift-json <report>/smoke-check-drift.json \
  --json-out <report>/smoke-check-notification.json \
  --md-out <report>/smoke-check-notification.md
```

## Validated Outcomes
- notification JSON generated successfully
- notification Markdown generated successfully
- drift summary was included when drift JSON existed
- top diagnostics were included in the compact notification output

## Notes
Webhook POST behavior was implemented but not exercised against a live endpoint during this repo session because no notification webhook URL was provided.
