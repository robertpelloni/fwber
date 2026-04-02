# Version Reference

- **Current version:** `1.0.71`
- **Release date:** `2026-04-02`
- **Release name:** `Plan-Aware Premium Pricing`

## Summary

This release turns the premium purchase flow into a real backend-defined plan system for the existing `gold_monthly` offer. Stripe intent creation, direct charges, token upgrades, webhook fulfillment, and the frontend modal now all key off the same configurable plan metadata instead of silently assuming one hardcoded monthly price.

## Version Sources of Truth

- Root version file: `VERSION`
- Root package metadata: `package.json`
- Frontend package metadata: `fwber-frontend/package.json`
- Historical release notes: `CHANGELOG.md`
