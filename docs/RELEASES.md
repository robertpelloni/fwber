# Release Notes

## v0.3.35 (Current) - Phase 5 Scale & Hardening
**Date:** February 19, 2026

### Features
*   **GDPR Data Export:** Users can now download their full profile and activity data via Settings > Account.
*   **Merchant Analytics UI:** Enhanced dashboard with detailed tooltips for K-Factor, Retention, and Conversion Funnels.
*   **Security Settings UI:** Added tooltips explaining E2E encryption and key regeneration.

### Infrastructure
*   **Database Scaling:** Enabled MySQL Read/Write splitting configuration in `config/database.php`.
*   **Kubernetes:** Production-ready manifests (`fwber-stack.yaml`) with Liveness/Readiness probes and resource limits.
*   **Performance Baseline:** Established initial load test metrics (55 RPS on dev) using `baseline_runner.py`.

### Fixes & Optimization
*   **Rate Limiting:** Verified `AppServiceProvider` rate limits for critical endpoints.
*   **Backend Monitoring:** Implemented `ApmMiddleware` to log slow requests (>100ms) for performance tuning.

---

## v0.3.34 - MVP Feature Complete
**Date:** February 06, 2026

*   **System Dashboard:** Real-time health monitoring linked to backend.
*   **Merchant Analytics:** Integrated real backend data for KPI tracking.
*   **Refactoring:** Removed legacy Mercure dependencies; unified on `useWebSocket` hook.
*   **Documentation:** Created `VISION.md`, `UNIVERSAL_LLM_INSTRUCTIONS.md`, and Handoff docs.

## v0.3.0 - Social Dynamics
**Date:** January 15, 2026

*   Groups, Video Chat, Token Economy.

## v0.2.0 - Enhanced UX
**Date:** December 20, 2025

*   Photo Gallery, Push Notifications, Privacy Controls.

## v0.1.0 - Core Foundation
**Date:** November 01, 2025

*   Auth, Profile, Geolocation, Basic Matching.
