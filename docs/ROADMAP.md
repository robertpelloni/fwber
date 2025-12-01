# Project Roadmap

**Last Updated:** December 02, 2025
**Status:** Production Hardening Phase

## 🟢 Current Phase: Production Hardening
The MVP and all secondary systems (Recommendations, AI Content, Chatrooms, Real-time) are complete. The focus is now on preparing the application for scale, security, and reliability.

### 🚧 Pending / In Progress

#### 1. Mobile Experience
- [x] **Push Notifications**: Implement backend support (WebPush, Subscriptions).
    - *Current Status*: Backend `PushMessage` and `PushNotificationService` implemented using `laravel-notification-channels/webpush`.

#### 2. Monetization
- [ ] **Payment Integration**: Replace `MockPaymentGateway` with real Stripe/Apple Pay integration.
- [ ] **Subscription Management**: Implement `SubscriptionController` logic.

### 🚀 Immediate Priorities (Q4 2025)

#### 1. Performance Optimization
- [x] **Redis Caching**: Implement caching for high-traffic endpoints:
    - `GET /api/recommendations/*`
    - `GET /api/proximity/feed`
    - `GET /api/matches`
- [x] **Database Indexing**: Audit and optimize queries for `matches`, `messages`, and `locations` tables.
- [x] **Frontend Optimization**:
    - Implement code splitting for heavy components (e.g., Map, AR).
    - Optimize image loading (Next.js Image optimization tuning).

#### 2. Security & Compliance
- [x] **Security Audit**: Manual review and header hardening (Automated tools deferred).
- [x] **Rate Limiting**: Tune `FEATURE_RATE_LIMITS` for production traffic patterns.
- [x] **Headers**: Enforce strict CSP and security headers (HSTS, X-Frame-Options).
- [x] **Data Retention**: Implement automated cleanup for old logs and ephemeral artifacts.

#### 3. Monitoring & Observability
- [x] **Error Tracking**: Integrate Sentry for Backend and Frontend.
- [x] **APM**: Setup Datadog or similar for performance monitoring (Scaffolding implemented).
- [x] **Uptime Monitoring**: Configure health check endpoints and external pingers.

#### 4. Mobile Experience (PWA)
- [x] **Service Worker**: Refine caching strategies for offline support.
- [x] **Push Notifications**: Implement Web Push for messages and matches.
- [x] **Installability**: Ensure "Add to Home Screen" flow is seamless.

### 🔮 Future Features (2026+)

#### 1. Advanced AI Features
- [x] **Media Analysis**: Real-time safety checks for media (Mocked implementation).

## ✅ Completed Milestones

### 2026 Features (Early Implementation)
- [x] **Premium Tiers**: "Gold" features (See who likes you, unlimited swipes).
- [x] **Boosts**: Temporary visibility increase.
- [x] **Events**: Location-based event discovery and ticketing.
- [x] **Groups**: Interest-based sub-communities.
- [x] **Behavioral Matching**: AI matching based on interaction patterns (Engagement, Activity, Content).

### MVP (Q3 2025)
- [x] Authentication & Profiles
- [x] Matching & Messaging
- [x] Photos & Safety
- [x] Location Services

### Secondary Systems (Nov 2025)
- [x] Recommendations Engine
- [x] AI Content Generation
- [x] Proximity Chatrooms
- [x] Face Reveal System
- [x] Real-time (WebSocket/Mercure)
- [x] Local Media Vault
