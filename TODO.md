# TODO — fwber Immediate Action Items

> **Version:** 1.0.45  
> **Last Updated:** 2026-04-02  

---

## 🔴 Critical: Bug Fixes & Stability
- [ ] **Fix 500 Errors on Production**: The `/api/location`, `/api/photos`, and `/api/safety/walk/active` endpoints are throwing 500 errors on DreamHost. Add explicit `Log::error($e)` to `bootstrap/app.php` exception handler to capture the silent trace and fix the root cause (likely validation, permissions, or missing env vars).
- [ ] **Verify Vercel Deployment**: Ensure the latest Next.js build with the `/api` absolute path proxy and MIME fixes is successfully deployed and running.

## 🟡 High: Missing UI Integrations
- [ ] **ActivityPub Federation UI**: The backend supports WebFinger, Inbox, and Outbox, but the frontend lacks a UI to search for federated users, follow them, or view the federated feed.
- [ ] **Merchant Portal UI**: Merchant registration, promotion creation, management, analytics, vibe analysis, live pulse broadcasting, and broadcast history are now wired, but the portal still needs deeper lifecycle tools like resend/deactivate/reporting controls and broader operations polish.
- [ ] **Interest Graph Phase 4**: Follow the shipped field-note + relationship-link slices with curated taxonomy/catalog APIs, topic hubs, and scene-based discovery around interests instead of pure proximity.
- [ ] **AI Wingman Enhancements**: The backend supports `compatibilityAudit`, `findNemesis`, and `predictFortune`. Ensure these are fully wired up in the chat interface with rich, animated UI components.
- [ ] **Hardware Token UI Polish**: Ensure the `app/settings/hardware/page.tsx` gracefully handles BLE pairing flows and visualizes the "ping" actions.

## 🟢 Medium: Robustness & Refactoring
- [ ] **Event Sourcing Audit**: Review all `EventStore::append` calls to ensure aggregate IDs are strictly cast to strings and that no integer-to-string database constraint errors exist.
- [ ] **Offline Sync Conflict Resolution**: Enhance `use-chat-sync.ts` to handle complex CRDT conflict resolution when merging offline messages with server state.
- [ ] **Rust Geo-Screener Integration**: Transition the PHP `LocationController` to offload dense spatial queries to the `fwber-geo` Rust microservice.

## ⚪ Low: Technical Debt & Documentation
- [ ] Update `cypress` E2E tests to cover the new ZK-Identity and AR Navigation flows.
- [ ] Clean up unused local test files and outdated `.txt` debug logs from the root directory.
- [ ] Ensure all new API endpoints are fully documented with Swagger/OpenAPI annotations.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
