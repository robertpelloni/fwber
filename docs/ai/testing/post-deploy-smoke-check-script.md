# Post-Deploy Smoke Check Script — Testing Notes

**Date:** 2026-04-04  
**Version:** 1.4.7

## Scope
This testing pass validates the new Hetzner-oriented smoke-check automation added at:
- `ops/hetzner/scripts/smoke-check.sh`

It also validates the deploy-script integration in:
- `ops/hetzner/scripts/deploy-backend.sh`

## Validation Performed
### Shell syntax validation
Executed from repo root:

```bash
bash -n ops/hetzner/scripts/smoke-check.sh
bash -n ops/hetzner/scripts/deploy-backend.sh
```

### Result
- syntax check passed for both scripts

## Additional Runtime Notes
A full end-to-end execution of `smoke-check.sh` against the final production topology was not performed in-repo because:
- the real Hetzner host is not provisioned within this session
- authenticated smoke tokens are not available in-repo
- the websocket upgrade probe requires the live Reverb app key

## Expected Production Validation
After the server exists, validate with something like:

```bash
FWBER_BACKEND_DIR=/var/www/fwber/repo/fwber-backend \
FWBER_REVERB_APP_KEY=... \
FWBER_USER_BEARER_TOKEN=... \
FWBER_MERCHANT_BEARER_TOKEN=... \
FWBER_MODERATOR_BEARER_TOKEN=... \
/var/www/fwber/repo/ops/hetzner/scripts/smoke-check.sh
```

## Pass Criteria
A successful production run should show:
- zero failures
- healthy `/api/health*` responses
- working invalid-login contract
- working roast preview contract
- reachable geo endpoint
- successful websocket upgrade when `FWBER_REVERB_APP_KEY` is supplied
- successful premium / merchant / moderation checks when the appropriate tokens are supplied
