# Project Status — fwber v1.0.6 (Payments, Notifications & Realtime Hardening)

**Date:** 2026-04-01  
**Version:** 1.0.6 "Payments, Notifications & Realtime Hardening"  
**Status:** 🚀 **PATCHED AND READY TO REDEPLOY**

---

## 🌐 Frontend (Vercel Edge Network)
- **Build**: Next.js 15.0.7 / React 18.3.1
- **Payments**: Stripe UI now stays disabled instead of crashing when the publishable key is missing.
- **Notifications**: Browser requests now use the shared API client and a dedicated unread-count endpoint.
- **Realtime**: Echo/Reverb initialization now no-ops cleanly when production websocket env is incomplete.

## 🔌 Backend (DreamHost Shared)
- **Notifications API**: Serializer now tolerates malformed legacy payloads and exposes `/api/notifications/count`.

## ✅ Release Focus
- [x] Removed empty-key Stripe crash path.
- [x] Hardened notification feed/count handling.
- [x] Reduced noisy production websocket failures when realtime env is incomplete.
- [ ] Awaiting production redeploy verification.
