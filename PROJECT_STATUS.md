# PROJECT_STATUS.md - fwber v1.8.10 (Rewind Sidebar Domain Organization)

**Date:** 2026-04-06
**Version:** 1.8.10 "Rewind Sidebar Domain Organization"
**Status:** ✅ **RESTORED SURFACES ARE NOW GROUPED BY PRODUCT DOMAIN IN BOTH THE DASHBOARD AND APP SHELL NAVIGATION**

---

## 🎯 What This Release Delivered
This release followed the dashboard organization pass by applying the same product-domain structure to the app shell navigation.

Delivered:
- reorganized the sidebar and mobile restored-surfaces navigation into clearer grouped sections
- aligned the shell with the dashboard’s domain-based product map
- caught and repaired a narrow production-only regression caused by the imported `Map` icon shadowing the global constructor in the new helper
- preserved green local production build validation without introducing backend-risk changes

## ✅ Why This Matters
The rewind branch now has enough restored destinations that shell organization matters as much as route restoration. Grouping the sidebar by domain makes the signed-in app easier to navigate, easier to demo, and more obviously centered on the user-approved core product areas rather than a flat list of recovered links.
