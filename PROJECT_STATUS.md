# Project Status — fwber v1.2.1 (Mobile & E2E Polish)

**Date:** 2026-04-04  
**Version:** 1.2.1 "Mobile & E2E Polish"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## 📱 Mobile Core & Background Location
- **Battery-Efficient Tracking:** Implemented OS-level background location tracking using `expo-location` and `TaskManager`. Proximity alerts now trigger even when the app is closed.
- **Push Notifications:** Fully integrated Expo Push Notifications with the Laravel backend. Users now receive native alerts for matches and new encrypted messages.
- **Token Persistence:** Added secure token storage via `expo-secure-store` to allow background tasks to communicate with the API.

## 🔐 E2E Key Backup & Restore
- **Multi-Device Parity:** Users can now back up their E2E private keys (encrypted with a passphrase) to the server.
- **Recovery Flow:** Added a proactive dashboard alert that guides users to restore their keys on new devices, ensuring chat history is never lost.
- **WASM Cleanse:** Gracefully handled environments where Rust/WASM is unavailable while maintaining high-speed WebCrypto fallbacks.

## 🔪 The Great Simplification (Finalized)
- **Aggressive Pruning:** Deleted an additional 36 legacy routes and dozens of unused components. The frontend route list has been reduced from 85 to 49 core pages.
- **Clean Documentation:** Updated the Help Center and developer docs to reflect the removed ActivityPub, Governance, and Economy features.

## ✅ Release Focus
- [x] Finalize Background Location tracking.
- [x] Integrate Push Notifications (Expo/FCM).
- [x] Build E2E Key Backup/Restore UI & Logic.
- [x] Finalize frontend bloat deletion.
- [x] Fix deprecated Lucide icon references causing build failures.
