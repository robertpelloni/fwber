# Project Status — fwber v1.0.44 (Field Notes and Journal Privacy)

**Date:** 2026-04-02  
**Version:** 1.0.44 "Field Notes and Journal Privacy"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Field Notes and Journal Privacy
- **Long-form Journals Shipped**: Users can now create private, friends-only, circle-only, or public field notes from a dedicated `/journal` page.
- **Reusable Visibility Layer Added**: Journal access reuses accepted friendships plus active group membership so `circle` content ships without inventing a second social graph.
- **Privacy Defaults Added**: Users can save default journal visibility and an optional default circle group from a dedicated settings page.
- **Profile Surface Wired**: Public profiles now render the subset of field notes the viewer is allowed to see.

## ✅ Release Focus
- [x] Added journal storage, visibility enforcement, and privacy-default APIs.
- [x] Added frontend authoring, navigation, and settings surfaces for field notes.
- [x] Exposed visible field notes on public profiles and covered the rules with backend feature tests.
