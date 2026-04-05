# TODO — fwber Immediate Action Items

> **Version:** 1.9.1
> **Last Updated:** 2026-04-05

---

## 🔴 Critical: Restoration Strategy Shift
- [ ] **Pre-Simplification Rewind Merge Plan**: Build a restoration branch from the last pre-`Great Simplification` snapshot and merge/replay the validated Hetzner deployment, CI, smoke-check, and live production hardening changes onto it.
- [ ] **Identify Exact Rewind Baseline**: Confirm the final pre-simplification commit/window to use as the restoration base (`v1.1.7` era) before doing any dangerous merge choreography on `main`.
- [ ] **Map Hetzner Delta To Replay Set**: Separate required modern infra/runtime commits from simplification-era deletions so the rewind branch gets old features plus current production deployment reality.

## 🔴 Critical: Deployment & Verification
- [ ] **Verify Latest Discovery Filter Restoration Live**: Confirm advanced `/matches` filters, premium token-gated filters, and profile persistence for diet/politics/religion work against the Hetzner backend.
- [ ] **Verify Latest Unlock + Discovery + Spend Surfaces Together**: Check wallet, boosts, gifts, unlocks, premium filters, referrals, video chat, and events in one signed-in production pass.
- [ ] **Production 500 Error Sweep**: Continue collecting and eliminating real production 500s before broadening restoration further.

## 🟡 High: Broad Feature Restoration
- [ ] **Reconstruct Removed Systems From Pre-Simplification Snapshot**: Restore removed surfaces in larger chunks from the pre-simplification baseline instead of continuing only one-off rebuilds from archive fragments.
- [ ] **Protect Hetzner Runtime During Restoration**: Keep the current Hetzner deployment contract, CI, smoke checks, ACL fixes, and DNS/TLS topology as the non-negotiable modern baseline while features are reintroduced.
- [ ] **DreamHost Backend Retirement**: Once the rewind-merge restoration is stable on Hetzner, fully decommission the old DreamHost backend path.

## ✅ Recently Completed
- [x] **Premium Discovery Filter Restoration**: Restored premium discovery schema, profile persistence, `/matches` premium filter passthrough, token gating, and expanded match filter UI.
- [x] **Token-Gated Unlock Surface Restoration**: Restored match insights unlocks, private photo unlocks, generic content unlock ledger, and frontend locked/unlocked UX for these token-spend surfaces.
- [x] **Profile Boost Restoration**: Restored boost purchase/status/history endpoints and reconnected the boost CTA on `/matches` to a real backend contract.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
