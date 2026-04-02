# Project Status — fwber v1.0.64 (Referral Commissions & Onboarding Skip Flow)

**Date:** 2026-04-02  
**Version:** 1.0.64 "Referral Commissions & Onboarding Skip Flow"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Referral Commissions & Onboarding Skip Flow
- **Onboarding Progression Repaired**: Optional onboarding steps no longer hard-stop the signup flow, including the user-reported fitness/physical screen, and the frontend now sanitizes blank or incompatible profile fields before saving.
- **Referral Code Backfill Added**: Legacy users who were missing `referral_code` now receive one automatically during auth/session and vouch-link flows, eliminating the `ref=null` links that were surfacing in Viral Rewards.
- **Two-Level Premium Referral Ledger Shipped**: Premium purchases now record idempotent level-1 and level-2 referral commissions, tracking pending USD payouts and awarding BobCoin/FWB rewards to uplines.
- **Viral Rewards UI Grounded in Real Data**: The dashboard referral modal now loads a dedicated referral summary API for invite links, vouch links, golden tickets, counts, and premium reward totals instead of guessing from nullable cached auth state.
- **Regression Coverage Expanded**: Backend referral and premium tests now cover referral-code backfill plus two-level premium commission payouts, and frontend Cypress coverage now verifies onboarding skip progression through optional steps.

## Current Referral/Signup Progress
- **Legacy Accounts Are Self-Healing**: Users do not need a manual migration step to start sharing invites; the runtime backfill path guarantees a usable referral code the next time they authenticate or open the vouch flow.
- **Cash Rewards Are Ledgered, Not Faked**: Premium referral cash is tracked as pending USD payout entries rather than being silently treated as already-withdrawn funds, keeping the wallet semantics honest.
- **UI and Backend Contract Are Aligned**: Referral links, vouch links, counts, and premium reward totals now come from a backend-owned summary shape, reducing drift between cached auth data and the real referral state.

## ✅ Release Focus
- [x] Removed onboarding blockers so optional signup/profile steps can be skipped and filled later.
- [x] Backfilled missing referral codes in runtime and via migration for legacy users.
- [x] Added a dedicated referral summary API and updated the Viral Rewards modal to use it.
- [x] Recorded two-level premium referral commissions with pending USD payouts and BobCoin rewards.
- [x] Added focused regression coverage for onboarding skip and premium referral commission flows.
