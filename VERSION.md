# Version Reference

- **Current version:** `1.0.67`
- **Release date:** `2026-04-02`
- **Release name:** `Premium Billing Hardening`

## Summary

This release hardens premium billing by requiring real Stripe payment proof, reroutes the visible upgrade pages back through the existing Stripe modal flow, fixes the Stripe webhook secret lookup, and adds concise homepage copy explaining the two-level premium referral/FWBcoin loop.

## Version Sources of Truth

- Root version file: `VERSION`
- Root package metadata: `package.json`
- Frontend package metadata: `fwber-frontend/package.json`
- Historical release notes: `CHANGELOG.md`
