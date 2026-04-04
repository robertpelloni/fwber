# PROJECT_STATUS.md - fwber v1.3.3 (Sentry Build Modernization)

**Date:** 2026-04-04
**Version:** 1.3.3 "Sentry Build Modernization"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## 🎯 What This Release Solved
This release removed the lingering Sentry setup debt that was polluting every frontend production build. The app was compiling, but the build output kept reporting outdated integration patterns:
- missing `onRequestError` hook in `instrumentation.ts`
- deprecated `sentry.client.config.ts` naming
- missing `onRouterTransitionStart` export for client navigation tracing
- deprecated Sentry webpack option usage

The build is now clean again.

## 🛠️ Modernized Sentry Integration
- **Server/edge instrumentation repaired:** `fwber-frontend/instrumentation.ts` now performs actual runtime registration and exports `onRequestError = Sentry.captureRequestError`.
- **Client instrumentation modernized:** introduced `fwber-frontend/instrumentation-client.ts` and exported `onRouterTransitionStart = Sentry.captureRouterTransitionStart` for App Router navigation instrumentation.
- **Legacy client config retired:** removed the old `fwber-frontend/sentry.client.config.ts` file that was triggering deprecation warnings.
- **Next config cleaned:** removed deprecated Sentry webpack plugin option usage from `fwber-frontend/next.config.js`.

## ✅ Validation
- **Frontend production build passed cleanly:**
  - `npm run build --prefix fwber-frontend`
- Result:
  - successful production build
  - Sentry-specific deprecation/action warnings eliminated

## ✅ Release Focus
- [x] Export Sentry request-error instrumentation in `instrumentation.ts`.
- [x] Move client instrumentation to `instrumentation-client.ts`.
- [x] Export router transition tracing hook for App Router.
- [x] Remove deprecated Sentry config/file patterns.
- [x] Re-run and confirm a clean frontend production build.
