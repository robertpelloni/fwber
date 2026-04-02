# Project Status — fwber v1.0.45 (Relationship Links)

**Date:** 2026-04-02  
**Version:** 1.0.45 "Relationship Links"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Relationship Links
- **Mutual Link Flow Shipped**: Accepted friends can now propose and confirm explicit relationship/status links instead of relying only on freeform profile text.
- **Visibility-Aware Profile Display**: Confirmed relationship links render on profiles only when the viewer satisfies the link visibility (`public`, `friends`, or owner/participant access).
- **Connections UI Expanded**: Friends & Connections now includes relationship-link creation, pending request review, editing, and removal.
- **Friendship Cleanup Preserved**: Removing a friendship now also removes any relationship link between that pair so the social graph stays consistent.

## ✅ Release Focus
- [x] Added relationship-link storage, request/confirm/update/delete APIs, and profile visibility handling.
- [x] Added Friends & Connections management UI for relationship links and pending confirmations.
- [x] Exposed visible relationship links on public profiles and covered the rules with backend feature tests.
