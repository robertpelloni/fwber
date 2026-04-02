# Project Status — fwber v1.0.35 (Federation Explorer Navigation)

**Date:** 2026-04-02  
**Version:** 1.0.35 "Federation Explorer Navigation"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Federation Explorer And Navigation Hardening
- **Actor Explorer Backend**: The backend now exposes authenticated remote actor detail plus actor-scoped cached post retrieval for federation UI drill-in flows.
- **Actor Explorer UI**: `/settings/federation/actors` now resolves remote actor identity, follow state, and cached posts from one shared frontend federation helper.
- **PWA Prompt Cleanup**: The duplicate homepage `PWAInstallPrompt` mount is gone, leaving one global install-prompt listener in the root layout.
- **Universal Subpage Navigation**: Shared app and merchant headers now provide consistent logo-home and back affordances, and a global fallback nav covers routes that still bypass those headers.

## ✅ Release Focus
- [x] Added backend federation actor-detail and actor-filtered post support.
- [x] Added the shared frontend federation API layer and actor explorer route.
- [x] Wired federation settings, activity, and feed pages into the explorer flow.
- [x] Removed duplicate PWA install-prompt mounting.
- [x] Added consistent home/back navigation to shared and non-shared subpage shells.
- [x] Revalidated backend federation coverage with `php artisan test tests/Feature/ActivityPubTest.php`.
- [x] Revalidated the frontend with `npm run type-check`.
- [x] Revalidated the frontend with `npm run build`.
