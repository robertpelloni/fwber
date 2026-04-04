# PROJECT_STATUS.md - fwber v1.4.1 (Hetzner Deployment Docs Refresh)

**Date:** 2026-04-04
**Version:** 1.4.1 "Hetzner Deployment Docs Refresh"
**Status:** ✅ **DOCUMENTED, VERIFIED, AND READY FOR VPS EXECUTION**

---

## 🎯 What This Release Delivered
This release converts deployment guidance from legacy DreamHost-centric instructions into the new recommended production architecture:

- **Frontend:** Vercel
- **Backend / Realtime / Geo / Data:** Hetzner VPS

It does not change active app runtime behavior; it updates the operational docs so the restored AI, premium, and merchant systems can be deployed on the infrastructure path we now actually recommend.

## 📚 Deployment Documentation Updated
- `DEPLOY.md` fully rewritten around Hetzner + Vercel
- new `docs/ai/deployment/hetzner-vercel-production.md`
- new `docs/deployment/HETZNER_VERCEL_DEPLOYMENT.md`
- `docs/deployment/DREAMHOST_DEPLOYMENT.md` converted to legacy-reference notice
- `docs/ai/deployment/stripe-production-rollout.md` updated to account for premium + marketplace Stripe usage and Hetzner-hosted backend
- `docs/ai/deployment/cloudflare-edge-caching.md` updated to reference Vercel + Hetzner instead of Vercel + DreamHost

## ✅ Why This Matters
After restoring:
- AI surface
- premium billing
- merchant marketplace

…the old DreamHost deployment narrative was no longer aligned with the actual active stack. fwber now expects stronger control over:
- Redis-backed queues/sessions/cache
- Reverb websocket runtime
- Rust geo service
- Stripe webhooks
- merchant/premium billing ledgers

This release closes that documentation gap.

## ✅ Validation
- Merchant + premium + AI + core backend tests were already green in the prior release wave.
- Frontend production build had already passed with merchant and premium routes visible.
- This release is documentation-focused and prepares the next real-world step: executing the Hetzner/Vercel deployment.

## ✅ Release Focus
- [x] Replace DreamHost-first deployment guidance with Hetzner/Vercel guidance.
- [x] Add dedicated production blueprint docs for the new topology.
- [x] Update Stripe and Cloudflare deployment docs to match the new hosting strategy.
- [x] Mark DreamHost deployment docs as legacy reference rather than active recommendation.
